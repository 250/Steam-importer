<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use ScriptFUSION\StaticClass;

final class Queries
{
    use StaticClass;

    private const APPS_WITH_SCORE =
        'SELECT *,
            (
                (positive_reviews + 1.9208) / total_reviews - 1.96
                    * SQRT((positive_reviews * negative_reviews) / total_reviews + 0.9604)
                    / total_reviews
            ) / (1 + 3.8416 / total_reviews) AS score
         FROM review'
    ;

    private const APPS_SORTED_BY_SCORE = self::APPS_WITH_SCORE . ' ORDER BY score DESC';

    private const TOP_250_GAMES = self::APPS_WITH_SCORE . ' WHERE app_type = "game" ORDER BY score DESC LIMIT 250';

    public static function fetchAppsSortedByScore(Connection $database): Statement
    {
        return $database->executeQuery(self::APPS_SORTED_BY_SCORE);
    }

    public static function fetchTop250Games(Connection $database): Statement
    {
        return $database->executeQuery(self::TOP_250_GAMES);
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
