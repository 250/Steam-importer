<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;

class Importer
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function import(): void
    {
        $porter = (new PorterFactory)->create();

        $this->logger->info('Beginning review list import...');
        $reviews = $porter->import(new GameReviewsListImportSpecification);

        $database = DriverManager::getConnection(['url' => 'sqlite:///games.sqlite']);
        $database->exec(
            'CREATE TABLE IF NOT EXISTS review (
                id INTEGER PRIMARY KEY,
                game_name TEXT,
                total_reviews INTEGER,
                positive_reviews INTEGER,
                negative_reviews INTEGER
            );'
        );

        foreach ($reviews as $review) {
            try {
                $database->insert('review', $review);
            } catch (UniqueConstraintViolationException $exception) {
                $this->logger->warning("Skipped #$review[id] $review[game_name] (already exists).");

                continue;
            }

            $this->logger->info(
                "Inserted #$review[id] $review[game_name]: +$review[positive_reviews] -$review[negative_reviews]}"
                    . " =$review[total_reviews]"
            );
        }

        $this->logger->info('Finished :^)');
    }
}
