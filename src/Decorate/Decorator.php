<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Decorate;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\Resource\InvalidAppIdException;
use ScriptFUSION\Porter\Provider\Steam\Scrape\ParserException;
use ScriptFUSION\Steam250\Database\Queries;
use ScriptFUSION\Top250\Shared\Algorithm;

/**
 * Decorates Steam games with missing information, such as whether they're actually a game.
 *
 * We could just decorate every app but it would take ages so we deliberate decorate the minimum necessary to build a
 * complete list for our use cases, such as the top 250 list.
 */
class Decorator
{
    private $porter;

    private $database;

    private $logger;

    private $algorithm;

    private $weight;

    public function __construct(
        Porter $porter,
        Connection $database,
        LoggerInterface $logger,
        Algorithm $algorithm,
        float $weight
    ) {
        $this->porter = $porter;
        $this->database = $database;
        $this->logger = $logger;
        $this->algorithm = $algorithm;
        $this->weight = $weight;
    }

    public function decorate(int $targetCount = 250, string $targetType = 'game'): void
    {
        $this->logger->info(
            "Decorating up to $targetCount \"$targetType\" apps sorted by \"$this->algorithm\" ($this->weight)."
        );

        $matched = 0;
        $cursor = Queries::fetchAppsSortedByScore($this->database, $this->algorithm, $this->weight);

        while ($matched < $targetCount && false !== $app = $cursor->fetch()) {
            // Data missing from database.
            if (!isset($app['app_type'])) {
                $this->logger->debug("Fetching missing info for #$app[id] $app[app_name]");

                try {
                    // Import missing data.
                    $details = $this->porter->importOne(new AppDetailsSpecification(+$app['id']));
                } catch (InvalidAppIdException | ParserException $exception) {
                    // App ID hidden, obsolete or region locked.
                    $this->logger->warning($exception->getMessage());

                    continue;
                }

                // Update database.
                $this->database->update('app', $details, ['id' => $app['id']]);

                // Update local state representation.
                $app = $details + $app;
            }

            if ($app['app_type'] === $targetType) {
                // Insert app rank into database.
                $this->database->executeQuery(
                    'INSERT OR REPLACE INTO rank (id, algorithm, rank, score) VALUES (?, ?, ?, ?)',
                    [
                        $app['id'],
                        "$this->algorithm$this->weight",
                        ++$matched,
                        $app['score'],
                    ]
                );
            }

            $this->logger->info("$matched/$targetCount #$app[id] ($app[app_name]) is $app[app_type].");
        }

        $this->logger->info('Finished :^)');
    }
}
