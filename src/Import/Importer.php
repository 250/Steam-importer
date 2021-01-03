<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Loop;
use Amp\Producer;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Async\Throttle\DualThrottle;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\Resource\InvalidAppIdException;
use ScriptFUSION\Porter\Provider\Steam\Scrape\SteamStoreException;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Retry\FailingTooHardException;
use ScriptFUSION\Steam250\Database\Queries;
use ScriptFUSION\Steam250\Import\Patreon\ApplistFormat;
use ScriptFUSION\Steam250\Import\SteamSpy\SteamSpySpecification;
use function Amp\Promise\any;

/**
 * Imports Steam app data into a database with chunking support.
 *
 * The specified list of apps is decorated by downloading additional data for each app.
 *
 * By default every app is saved in the database whether or not it was successfully decorated. This is to avoid pausing
 * to redecorate the same item if the import is restarted. "Lite" mode avoids saving undecorated records to the
 * database, to save space, creating a "lighter" database.
 */
class Importer
{
    public const DEFAULT_CHUNKS = 0;
    public const DEFAULT_CHUNK_INDEX = 1;

    private Porter $porter;
    private AppDetailsImporter $appDetailsImporter;
    private Connection $database;
    private LoggerInterface $logger;
    private string $appListPath;
    private DualThrottle $throttle;
    private int $chunks = self::DEFAULT_CHUNKS;
    private int $chunkIndex = self::DEFAULT_CHUNK_INDEX;
    private bool $lite = false;
    private string $steamSpyPath;

    private static array $steamSpyData;

    public function __construct(
        Porter $porter,
        AppDetailsImporter $appDetailsImporter,
        Connection $database,
        LoggerInterface $logger,
        string $appListPath
    ) {
        $this->porter = $porter;
        $this->appDetailsImporter = $appDetailsImporter;
        $this->database = $database;
        $this->logger = $logger;
        $this->appListPath = $appListPath;
        $this->throttle = new DualThrottle(70);
    }

    public function import(): void
    {
        $this->logger->debug('Detecting applist format...');
        $format = $this->detectApplistFormat();
        $specification = $this->fetchApps($format);

        $this->logger->info("Reading applist format: \"$format\"...");
        $apps = $this->porter->import($specification);
        $total = \count($apps);

        $this->logger->info('Starting Steam app details import...');
        $this->chunks && $this->logger->info("Processing chunk $this->chunkIndex of $this->chunks.");

        $appDetails = new Producer(function (\Closure $emit) use ($apps, $total) {
            $importQ = new DualThrottle(PHP_INT_MAX, PHP_INT_MAX);
            $count = 0;

            foreach ($apps as $app) {
                ++$count;

                if (Queries::doesAppExist($this->database, $app['id'])) {
                    $this->logger->warning(
                        'Skipped %app%: already exists.',
                        compact('app', 'total', 'count')
                    );

                    continue;
                }

                // Wait for throttle before adding new job. Might need to be in loop.
                yield $this->throttle->join();

                // Import app details.
                $importQ->await($appImport = ($this->appDetailsImporter)($this->porter, $app['id'], $this->throttle));

                $appImport->onResolve(
                    function (?\Throwable $throwable, ?array $appDetails) use ($emit, $app, $count, $total) {
                        if (!$throwable) {
                            // Overwrite name with imported name, preserving only the original ID.
                            return yield $emit([$appDetails + ['id' => $app['id']], $count]);
                        }

                        $context = compact('app', 'total', 'count');

                        if ($throwable instanceof SteamStoreException) {
                            // Usually due to region block.
                            $this->logger->warning("Steam error %app%: {$throwable->getMessage()}", $context);
                        } elseif ($throwable instanceof InvalidAppIdException) {
                            // Store page is redirecting.
                            $this->logger->warning("Invalid %app%", $context);
                        } elseif ($throwable instanceof ServerFatalException) {
                            $this->logger->error(
                                "Error %app%: {$throwable->getMessage()}",
                                $context
                            );
                        } elseif ($throwable instanceof FailingTooHardException) {
                            $prev = $throwable->getPrevious();
                            $this->logger->critical(
                                'Critical error %app%: [' . get_class($prev) . "] {$prev->getMessage()}",
                                $context
                            );
                        } else {
                            throw $throwable;
                        }
                    }
                );
            }

            // Wait for all jobs to finish enqueuing.
            yield any($importQ->getAwaiting());

            // Wait for all imports to complete.
            yield $this->throttle->getAwaiting();
        });

        Loop::run(function () use ($appDetails, $total) {
            $payloadCount = 0;

            while (yield $appDetails->advance()) {
                $this->processAppPayload($appDetails->getCurrent(), $total);

                if (!(++$payloadCount % $this->throttle->getMaxConcurrency())
                    && $this->database->isTransactionActive()
                ) {
                    $this->database->commit();
                    $this->logger->debug("Committed batch of {$this->throttle->getMaxConcurrency()}.");
                }
            }

            $this->database->isTransactionActive() && $this->database->commit();
        });

        $this->logger->info('Finished :^)');
    }

    private function processAppPayload(array $payload, int $total): void
    {
        [$app, $count] = $payload;
        $logContext = compact('app', 'total', 'count') + ['throttle' => $this->throttle];

        // Data unavailable.
        if (!isset($app['type'])) {
            if ($this->lite) {
                $this->logger->notice('Skipped %app%: invalid.', $logContext);

                return;
            }

            $this->logger->debug("#$app[id] $app[name]: invalid.", $logContext);
        }

        // No reviews.
        if (isset($app['total_reviews']) && $app['total_reviews'] < 1) {
            // Keep games with no reviews, due out in the next 8 days, for the previews page.
            if ($this->lite && ($app['release_date'] <= time() || $app['release_date'] > strtotime('8day'))) {
                $this->logger->notice('Skipped %app%: no reviews.', $logContext);

                return;
            }

            $this->logger->debug('%app%: no reviews.', $logContext);
        }

        if ($this->steamSpyPath) {
            $this->decorateWithSteamSpyData($app);
        }

        $this->database->isTransactionActive() || $this->database->beginTransaction();

        // Insert tags.
        foreach ($app['tags'] ?? [] as $tag) {
            $this->database->executeUpdate(
                /* On 2018/07/21 "Early Access" started appearing twice with different counts. To avoid violating
                   the unique integrity constraint, take the greater of the two and discard any others. */
                "INSERT OR IGNORE INTO app_tag (app_id, tag, votes)
                    VALUES ($app[id], :name, :count)",
                $tag
            );
        }
        unset($app['tags']);

        // Insert developers.
        foreach ($app['developers'] ?? [] as $developer) {
            Queries::insertDeveloper($this->database, [$app['id'], $developer]);
        }
        unset($app['developers']);

        // Insert publishers.
        foreach ($app['publishers'] ?? [] as $publisher) {
            Queries::insertPublisher($this->database, [$app['id'], $publisher]);
        }
        unset($app['publishers']);

        /*
         * Insert data. In normal mode undecorated records are inserted for idempotence, allowing import to be
         * quickly resumed later.
         */
        $this->database->insert('app', $app);

        $this->logger->info('Inserted %app%.', $logContext);
    }

    private function decorateWithSteamSpyData(array &$app): void
    {
        self::$steamSpyData ??=
            iterator_to_array($this->porter->import(new SteamSpySpecification($this->steamSpyPath)));

        if (!isset(self::$steamSpyData[$app['id']])) {
            $this->logger->debug('No Steam Spy data found for %app%.', compact('app'));

            return;
        }

        $app += self::$steamSpyData[$app['id']];
    }

    public function setChunks(int $chunks): void
    {
        $this->chunks = $chunks;
    }

    public function setChunkIndex(int $chunkIndex): void
    {
        $this->chunkIndex = $chunkIndex;
    }

    public function setLite(bool $lite): void
    {
        $this->lite = $lite;
    }

    public function setSteamSpyPath(string $steamSpyPath): void
    {
        $this->steamSpyPath = $steamSpyPath;
    }

    public function setAppDetailsImporter(AppDetailsImporter $appDetailsImporter): void
    {
        $this->appDetailsImporter = $appDetailsImporter;
    }

    private function detectApplistFormat(): ApplistFormat
    {
        $info = new \finfo(FILEINFO_MIME_ENCODING);

        if ($info->file($this->appListPath) === 'us-ascii') {
            return ApplistFormat::CLUB250();
        }

        return ApplistFormat::STEAM();
    }

    private function fetchApps(ApplistFormat $format): ImportSpecification
    {
        switch ($format) {
            case ApplistFormat::STEAM:
                return new SteamAppListSpecification($this->appListPath, $this->chunks, $this->chunkIndex);
            case ApplistFormat::CLUB250:
                return new Club250AppListSpecification($this->appListPath, $this->chunks, $this->chunkIndex);
        }
    }
}
