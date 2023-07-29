<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Porter;

final class TagsImporter
{
    public function __construct(
        private readonly Porter $porter,
        private readonly Connection $database,
        private readonly LoggerInterface $logger
    ) {
    }

    public function import(): void
    {
        $this->logger->info('Begin importing tag categories from Club 250.');

        foreach ($this->porter->import((new Import(new GetClub250TagCategories()))->enableCache()) as $category) {
            $this->database->executeStatement(
                'INSERT OR IGNORE INTO tag_cat (id, name, short_name) VALUES (:id, :name, :short_name)',
                $category
            );
        }

        $this->logger->info('Finished importing tag categories.');

        $this->logger->info('Begin importing tags from Club 250.');

        foreach ($this->porter->import((new Import(new GetClub250Tags()))->enableCache()) as $tag) {
            $this->database->executeStatement(
                'INSERT OR REPLACE INTO tag (id, name, category) VALUES (:id, :name, :category)',
                $tag
            );

            echo '.';
        }

        echo PHP_EOL;

        $this->logger->info('All done :^)');
    }
}
