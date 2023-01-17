<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use ScriptFUSION\StaticClass;
use Symfony\Component\DomCrawler\Crawler;

final class SteamChartsParser
{
    use StaticClass;

    public static function parseChart(string $html): array
    {
        $crawler = new Crawler($html);

        $dataRows = $crawler->filter('#top-games > tbody > tr');

        if (\count($dataRows) !== 25) {
            throw new HtmlParserException("Excepted 25 data rows: encountered {$dataRows->count()}.");
        }

        return $dataRows->each(static function (Crawler $tr): array {
            return [
                'app_id' => +substr(strrchr($tr->filter('a[href^="/app/"]')->attr('href'), '/'), 1),
                'peak_concurrent_players_30d' => $tr->children('.peak-concurrent')->text(),
            ];
        });
    }
}
