<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use ScriptFUSION\StaticClass;

final class QueryFragment
{
    use StaticClass;

    /**
     * @param float $z Optional. Z value derived from confidence level (see probability table). Default value
     *     represents 95% confidence.
     *
     * @return string
     *
     * @see http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
     * @see https://en.wikipedia.org/wiki/Checking_whether_a_coin_is_fair#Estimator_of_true_probability
     */
    public static function calculateWilsonScore(float $z = 1.96): string
    {
        return
            "(
                (positive_reviews + POWER($z, 2) / 2) / total_reviews - $z
                    * SQRT((positive_reviews * negative_reviews) / total_reviews + POWER($z, 2) / 4)
                    / total_reviews
            ) / (1 + POWER($z, 2) / total_reviews) AS score
            FROM app"
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

    public static function calculateLaplaceLogScore(float $weight): string
    {
        return
            "(
                positive_reviews * 1. / total_reviews * LOG10(total_reviews + 1) + $weight
            ) / (LOG10(total_reviews + 1) + $weight * 2.) AS score
            FROM app"
        ;
    }

    public static function calculateTornScore(): string
    {
        return '
            (positive_reviews * 1. / total_reviews)
                - ((positive_reviews * 1. / total_reviews) - .5) * POWER(2, -LOG10(total_reviews + 1)) AS score
            FROM app'
        ;
    }
}
