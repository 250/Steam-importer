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

        // Clear existing stories so any removed upstream disappear on refresh.
        $this->database->executeStatement('DELETE FROM c250_stories');

        // The Club 250 API emits stories in display order; record that order as the priority.
        $priority = 0;

        foreach ($this->porter->import(
            new Import(new GetClub250Stories($apiToken))
        ) as $story) {
            $this->logger->info("Story #$story[id].");

            $this->database->executeStatement(
                'INSERT OR REPLACE INTO c250_stories (id, app_id, slug, label, headline, summary, priority)
                    VALUES (:id, :app_id, :slug, :label, :headline, :summary, :priority)',
                ['priority' => $priority++] + $story,
            );
        }

        $this->logger->info('Finished importing stories.');

        $this->logger->info('All done :^)');
    }
}
