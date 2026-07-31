<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Porter;

final readonly class StoriesImporter
{
    public function __construct(
        private Porter $porter,
        private Connection $database,
        private LoggerInterface $logger
    ) {
    }

    public function import(string $apiToken): void
    {
        $this->logger->info('Begin importing stories from Club 250.');

        foreach ($this->porter->import(
            new Import(new GetClub250Stories($apiToken))
        ) as $story) {
            $this->logger->info("Story #$story[id].");

            $this->database->executeStatement(
                'INSERT OR REPLACE INTO c250_stories (id, app_id, label, headline, summary)
                    VALUES (:id, :app_id, :label, :headline, :summary)',
                $story,
            );
        }

        $this->logger->info('Finished importing stories.');

        $this->logger->info('All done :^)');
    }
}
