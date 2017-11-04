<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Porter;

class Importer
{
    public const DEFAULT_CHUNKS = 0;
    public const DEFAULT_CHUNK_INDEX = 1;

    private $porter;

    private $database;

    private $logger;

    private $chunks = self::DEFAULT_CHUNKS;

    private $chunkIndex = self::DEFAULT_CHUNK_INDEX;

    public function __construct(Porter $porter, Connection $database, LoggerInterface $logger)
    {
        $this->porter = $porter;
        $this->database = $database;
        $this->logger = $logger;
    }

    public function import(): void
    {
        $this->logger->info('Starting Steam games import...');
        $this->chunks && $this->logger->info("Processing chunk $this->chunkIndex of $this->chunks.");

        $reviews = $this->porter->import(new GameReviewsListSpecification($this->chunks, $this->chunkIndex));

        $total = count($reviews);
        $count = 0;

        foreach ($reviews as $review) {
            $percent = (++$count / $total) * 100 | 0;

            if (!isset($review['total_reviews']) || $review['total_reviews'] < 1) {
                $this->logger->warning("Skipped #$review[id] $review[app_name]: no reviews");

                continue;
            }

            try {
                $this->database->insert('review', $review);
            } catch (UniqueConstraintViolationException $exception) {
                $this->logger->warning("Skipped #$review[id] $review[app_name]: already exists.");

                continue;
            }

            $this->logger->info(
                "Inserted $count/$total ($percent%) #$review[id] $review[app_name]: ($review[total_reviews] reviews)."
            );
        }

        $this->logger->info('Finished :^)');
    }

    public function setChunks(int $chunks): void
    {
        $this->chunks = $chunks;
    }

    public function setChunkIndex(int $chunkIndex): void
    {
        $this->chunkIndex = $chunkIndex;
    }
}
