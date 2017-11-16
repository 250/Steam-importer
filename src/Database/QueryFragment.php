<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use ScriptFUSION\StaticClass;

final class QueryFragment
{
    use StaticClass;

    public static function calculateWilsonScore(): string
    {
        return
            '(
                (positive_reviews + 1.9208) / total_reviews - 1.96
                    * SQRT((positive_reviews * negative_reviews) / total_reviews + 0.9604)
                    / total_reviews
            ) / (1 + 3.8416 / total_reviews) AS score
            FROM app'
        ;
    }

    public static function calculateBayesianScore(float $weight): string
    {
        return
             "CASE 
                 WHEN (total_reviews * $weight * 1. / agg.max_votes) > 1
                 THEN (positive_reviews * 1. / total_reviews)
                 ELSE (total_reviews * $weight * 1. / agg.max_votes) * (positive_reviews * 1. / total_reviews)
                    + (1 - (total_reviews * $weight * 1. / agg.max_votes)) * agg.avg_score
             END score
             FROM app,
                (
                    SELECT 
                        AVG(positive_reviews * 1. / total_reviews) AS avg_score,
                        MAX(total_reviews) AS max_votes
                    FROM app
                ) agg"
        ;
    }

    public static function calculateLaplaceScore(float $weight): string
    {
        return
            "(positive_reviews + $weight) / (total_reviews + $weight * 2.) AS score
            FROM app"
        ;
    }

    public static function calculateLaplaceLogScore(): string
    {
        return
            '(
                positive_reviews * 1. / total_reviews * LOG10(total_reviews + 1) + .5
            ) / (LOG10(total_reviews + 1) + 1) AS score
            FROM app'
        ;
    }

    public static function calculateTornScore(): string
    {
        return
            '(positive_reviews * 1. / total_reviews)
                - POWER(((positive_reviews * 1. / total_reviews) - .5) * 2, -LOG10(total_reviews + 1)) AS score
            FROM app'
        ;
    }
}
