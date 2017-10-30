<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Porter;

class Importer
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

    public function import(): void
    {
        $this->logger->info('Starting Steam games import...');
        $reviews = $this->porter->import(new GameReviewsListSpecification);

        foreach ($reviews as $review) {
            try {
                $this->database->insert('review', $review);
            } catch (UniqueConstraintViolationException $exception) {
                $this->logger->warning("Skipped #$review[id] $review[game_name] (already exists).");

                continue;
            }

            $this->logger->info(
                "Inserted #$review[id] $review[game_name]: +$review[positive_reviews] -$review[negative_reviews]"
                    . " =$review[total_reviews]"
            );
        }

        $this->logger->info('Finished :^)');
    }
}
