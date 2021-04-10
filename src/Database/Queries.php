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
        return $database->fetchOne("SELECT id FROM app WHERE id = $appId") !== false;
    }

    public static function stitchReviewChunks(Connection $database, $chunkPath): bool
    {
        return $database->executeStatement(
            "ATTACH '$chunkPath' AS chunk;
            INSERT OR IGNORE INTO app SELECT * FROM chunk.app;
            INSERT OR IGNORE INTO tag SELECT * FROM chunk.tag;
            INSERT OR IGNORE INTO app_tag SELECT * FROM chunk.app_tag;
            INSERT OR IGNORE INTO app_developer SELECT * FROM chunk.app_developer;
            INSERT OR IGNORE INTO app_publisher SELECT * FROM chunk.app_publisher;
            DETACH chunk"
        ) > 0;
    }

    /**
     * Insert developer name, ignoring any duplicates. Some apps specify the developer name multiple times.
     */
    public static function insertDeveloper(Connection $database, array $developers): bool
    {
        return $database->executeStatement(
            'INSERT OR IGNORE INTO app_developer VALUES (?, ?, ?)',
            $developers
        ) > 0;
    }

    public static function insertPublisher(Connection $database, array $publishers): bool
    {
        return $database->executeStatement(
            'INSERT OR IGNORE INTO app_publisher VALUES (?, ?, ?)',
            $publishers
        ) > 0;
    }
}
