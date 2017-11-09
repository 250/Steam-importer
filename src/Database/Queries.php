<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use ScriptFUSION\StaticClass;
use ScriptFUSION\Top250\Shared\SharedQueries;

final class Queries
{
    use StaticClass;

    public static function fetchAppsSortedByScore(Connection $database): Statement
    {
        return $database->executeQuery(
            'SELECT *, '
            . SharedQueries::APP_SCORE
            . ' FROM review ORDER BY score DESC'
        );
    }

    public static function stitchReviewChunks(Connection $database, $chunkPath): bool
    {
        return $database->exec(
            "ATTACH '$chunkPath' AS chunk;
            INSERT OR IGNORE INTO review SELECT * FROM chunk.review;
            DETACH chunk"
        ) > 0;
    }
}
