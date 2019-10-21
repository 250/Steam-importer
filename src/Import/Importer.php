<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Loop;
use Amp\Producer;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Async\Throttle\Throttle;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\Resource\InvalidAppIdException;
use ScriptFUSION\Porter\Provider\Steam\Scrape\ParserException;
use ScriptFUSION\Steam250\Database\Queries;
use ScriptFUSION\Steam250\Import\SteamSpy\SteamSpySpecification;

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

    private $porter;
    private $appDetailsImporter;
    private $database;
    private $logger;
    private $appListPath;
    private $throttle;
    private $chunks = self::DEFAULT_CHUNKS;
    private $chunkIndex = self::DEFAULT_CHUNK_INDEX;
    private $lite = false;
    private $steamSpyPath;

    private static $steamSpyData;

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
        $this->throttle = new Throttle;
    }

    public function import(): void
    {
        $this->logger->info('Starting Steam games import...');
        $this->chunks && $this->logger->info("Processing chunk $this->chunkIndex of $this->chunks.");

        $apps = $this->porter->import(
            new AppListSpecification($this->appListPath, $this->chunks, $this->chunkIndex)
        );

        $total = \count($apps);

        $appDetails = new Producer(function (\Closure $emit) use ($apps, $total) {
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

                yield $this->throttle->await($emit(
                    \Amp\call(function () use ($app, $count, $total) {
                        try {
                            // Decorate app with full data set.
                            $app += yield ($this->appDetailsImporter)($this->porter, $app['id']);
                        } catch (InvalidAppIdException | ParserException $exception) {
                            // This is fine 🔥.
                        } catch (ServerFatalException $exception) {
                            $this->logger->error(
                                "Error %app%: {$exception->getMessage()}",
                                compact('app', 'total', 'count')
                            );
                        }

                        return [$app, $count];
                    })
                ));
            }

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
            if ($this->lite) {
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
        self::$steamSpyData || self::$steamSpyData =
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
}
