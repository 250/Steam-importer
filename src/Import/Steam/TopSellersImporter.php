<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Steam;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\Resource\ScrapeGlobalTopSellers;

final readonly class TopSellersImporter
{
    public function __construct(
        private Porter $porter,
        private Connection $database,
        private LoggerInterface $logger,
    ) {
    }

    public function import(): bool
    {
        $this->logger->info('Starting global top sellers import...');

        $this->database->executeStatement('DELETE FROM global_top_sellers');

        $rank = 0;
        foreach ($this->porter->import(new Import(new ScrapeGlobalTopSellers)) as $record) {
            if (++$rank > 100) {
                break;
            }

            $this->logger->debug("Inserting rank #$rank app ID #$record[app_id]...", ['count' => $rank, 'total' => 10]);

            $this->database->executeStatement(
                'INSERT OR IGNORE INTO global_top_sellers (rank, app_id) VALUES (?, ?)',
                [$rank, $record['app_id']]
            );
        }

        $this->logger->info('Finished :^)');

        return true;
    }
}
