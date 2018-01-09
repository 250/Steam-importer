<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Stitch;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Steam250\Database\Queries;

final class DatabaseStitcher
{
    private $database;

    private $logger;

    public function __construct(Connection $database, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    public function stitch()
    {
        $dbDir = \dirname($this->database->getParams()['path']);
        $this->logger->info("Starting stitch in \"$dbDir\".");

        $chunks = [];

        foreach (new \GlobIterator("$dbDir/*.sqlite.p*", \GlobIterator::CURRENT_AS_PATHNAME) as $chunk) {
            $this->logger->info("Merging: \"$chunk\".");

            Queries::stitchReviewChunks($this->database, $chunk);

            $chunks[] = $chunk;
        }

        foreach ($chunks as $chunk) {
            $this->logger->info("Deleting: \"$chunk\".");

            unlink($chunk);
        }

        $this->logger->info('Finished :^)');
    }
}
