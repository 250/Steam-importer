<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use ScriptFUSION\StaticClass;

final class Queries
{
    use StaticClass;

    public static function stitchReviewChunks(Connection $database, $chunkPath): bool
    {
        return $database->exec(
            "ATTACH '$chunkPath' AS chunk;
            INSERT OR IGNORE INTO app SELECT * FROM chunk.app;
            DETACH chunk"
        ) > 0;
    }
}
