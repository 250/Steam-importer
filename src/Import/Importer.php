<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\Resource\InvalidAppIdException;
use ScriptFUSION\Porter\Provider\Steam\Scrape\ParserException;
use ScriptFUSION\Steam250\Database\Queries;

class Importer
{
    public const DEFAULT_CHUNKS = 0;
    public const DEFAULT_CHUNK_INDEX = 1;

    private $porter;
    private $database;
    private $logger;
    private $appListPath;
    private $chunks = self::DEFAULT_CHUNKS;
    private $chunkIndex = self::DEFAULT_CHUNK_INDEX;

    public function __construct(Porter $porter, Connection $database, LoggerInterface $logger, string $appListPath)
    {
        $this->porter = $porter;
        $this->database = $database;
        $this->logger = $logger;
        $this->appListPath = $appListPath;
    }

    public function import(): void
    {
        $this->logger->info('Starting Steam games import...');
        $this->chunks && $this->logger->info("Processing chunk $this->chunkIndex of $this->chunks.");

        $reviews = $this->porter->import(
            new GameReviewsListSpecification($this->appListPath, $this->chunks, $this->chunkIndex)
        );

        $total = count($reviews);
        $count = 0;

        foreach ($reviews as $review) {
            $percent = (++$count / $total) * 100 | 0;

            if (Queries::doesAppExist($this->database, $review['id'])) {
                $this->logger->warning(
                    "Skipped $count/$total ($percent%) #$review[id] $review[app_name]: already exists."
                );

                continue;
            }

            try {
                $review += $this->porter->importOne(new GameReviewsSpecification($review['id']));
            } catch (InvalidAppIdException | ParserException $exception) {
                // This is fine.
            }

            if (!isset($review['app_type'])) {
                $this->logger->warning("#$review[id] $review[app_name]: invalid.");
            }

            if (isset($review['total_reviews']) && $review['total_reviews'] < 1) {
                $this->logger->warning("#$review[id] $review[app_name]: no reviews.");
            }

            // Insert data.
            $this->database->insert('app', $review);
            $this->logger->info("Inserted $count/$total ($percent%) #$review[id] $review[app_name].");
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
