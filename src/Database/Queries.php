<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use ScriptFUSION\StaticClass;

final class Queries
{
    use StaticClass;

    public static function doesAppExist(Connection $database, int $appId): bool
    {
        return $database->fetchColumn("SELECT id FROM app WHERE id = $appId") !== false;
    }

    public static function stitchReviewChunks(Connection $database, $chunkPath): bool
    {
        return $database->exec(
            "ATTACH '$chunkPath' AS chunk;
            INSERT OR IGNORE INTO app SELECT * FROM chunk.app;
            INSERT OR IGNORE INTO app_tag SELECT * FROM chunk.app_tag;
            DETACH chunk"
        ) > 0;
    }
}
