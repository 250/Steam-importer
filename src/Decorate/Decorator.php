<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Decorate;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\Resource\InvalidAppIdException;
use ScriptFUSION\Porter\Provider\Steam\Scrape\ParserException;
use ScriptFUSION\Steam250\Database\Queries;

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

    public function __construct(Porter $porter, Connection $database, LoggerInterface $logger)
    {
        $this->porter = $porter;
        $this->database = $database;
        $this->logger = $logger;
    }

    public function decorate(int $targetCount = 250, string $targetType = 'game'): void
    {
        $this->logger->info("Starting decoration of up to $targetCount apps of type \"$targetType\".");

        $matched = 0;
        $cursor = Queries::fetchAppsSortedByScore($this->database);

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
                $this->database->update('review', $details, ['id' => $app['id']]);

                // Update local state representation.
                $app = $details + $app;
            }

            $app['app_type'] === $targetType && ++$matched;

            $this->logger->info("$matched/$targetCount #$app[id] ($app[app_name]) identifies as $app[app_type].");
        }

        $this->logger->info('Finished :^)');
    }
}
