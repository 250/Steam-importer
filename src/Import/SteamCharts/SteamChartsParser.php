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
                'app_id' => substr(strrchr($tr->filter('a[href^="/app/"]')->attr('href'), '/'), 1),
                'peak_concurrent_players_30d' => $tr->children('.peak-concurrent')->text(),
            ];
        });
    }

    private static function parseChartData(Crawler $crawler): array
    {
        return $crawler->filter('script')->each(static function (Crawler $script) {
            return self::parseChartJavaScript($script->text());
        });
    }

    private static function parseChartJavaScript(string $javaScript): array
    {
        if (!self::validateChartJavaScript($javaScript)) {
            return null;
        }

        static $nl = "\n";

        $line = trim(strtok($javaScript, $nl));

        do {
            if ($line === '') {
                continue;
            }

            /*
             * Regex parser:
             * elem = app\.e\('spark_(?<app_id>\d+)'\);.*?elem\.datay = \[(?<hours>[\d,]+)\];
             */
        } while (($line = trim(strtok($nl))) !== false);
    }

    private static function validateChartJavaScript(string $javascript): bool
    {
        return (bool)preg_match('[elem = app\.e\(\'spark_\d+\'\);$.*elem\.datay = \[[\d,]+\];$]', $javascript);
    }
}
