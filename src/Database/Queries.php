<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use ScriptFUSION\StaticClass;
use ScriptFUSION\Top250\Shared\Algorithm;

final class Queries
{
    use StaticClass;

    public static function fetchAppsSortedByScore(
        Connection $database,
        Algorithm $algorithm,
        float $weight
    ): Statement {
        return $database->executeQuery(
            'SELECT *, '
            . self::getQueryFragment($algorithm, $weight)
            . ' ORDER BY score DESC'
        );
    }

    private static function getQueryFragment(Algorithm $algorithm, float $weight): string
    {
        switch ($algorithm) {
            case Algorithm::WILSON:
                return QueryFragment::calculateWilsonScore($weight);

            case Algorithm::BAYESIAN:
                return QueryFragment::calculateBayesianScore($weight);

            case Algorithm::LAPLACE:
                return QueryFragment::calculateLaplaceScore($weight);

            case Algorithm::LAPLACE_LOG:
                return QueryFragment::calculateLaplaceLogScore($weight);

            case Algorithm::TORN:
                return QueryFragment::calculateTornScore($weight);
        }
    }

    public static function stitchReviewChunks(Connection $database, $chunkPath): bool
    {
        return $database->exec(
            "ATTACH '$chunkPath' AS chunk;
            INSERT OR IGNORE INTO app SELECT * FROM chunk.app;
            DETACH chunk"
        ) > 0;
    }
}
