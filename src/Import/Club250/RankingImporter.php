<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Porter;

final readonly class RankingImporter
{
    public function __construct(
        private Porter $porter,
        private Connection $database,
        private LoggerInterface $logger
    ) {
    }

    public function import(string $apiToken): void
    {
        $this->logger->info('Begin importing ranking from Club 250.');

        $rank = 0;

        foreach ($this->porter->importOne(
            new Import(new GetClub250Trending($apiToken))
        ) as $appId) {
            ++$rank;
            $this->logger->info("App #$appId\n");

            $this->database->executeStatement(
                'INSERT OR REPLACE INTO c250_ranking (id, rank, app_id) VALUES ("TREND", :rank, :appId)',
                compact('rank', 'appId'),
            );
        }

        $this->logger->info('Finished importing ranking.');

        $this->logger->info('All done :^)');
    }
}
