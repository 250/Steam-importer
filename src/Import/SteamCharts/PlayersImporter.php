<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use Amp\Loop;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Specification\AsyncImportSpecification;

class PlayersImporter
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

    public function import(): bool
    {
        $resource = new GetCurrentPlayers;
        $resource->setLogger($this->logger);
        $players = $this->porter->importAsync(new AsyncImportSpecification($resource));

        $this->logger->info('Starting players import...');

        Loop::run(function () use ($players): \Generator {
            while (yield $players->advance()) {
                $app = $players->getCurrent();

                $this->logger->debug(sprintf('Inserting app ID #%s...', $app['app_id']));
                $this->database->executeUpdate(
                    'INSERT OR IGNORE INTO app_players VALUES (:app_id, :peak_concurrent_players_30d)',
                    $app
                );
            }
        });

        $this->logger->info('Finished :^)');

        return true;
    }
}
