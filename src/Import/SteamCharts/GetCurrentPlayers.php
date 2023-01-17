<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use Amp\Future;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;
use function Amp\async;

class GetCurrentPlayers implements ProviderResource
{
    private const BASE_URL = 'https://steamcharts.com/top/p.';

    private const PAGES = 80;

    private LoggerInterface $logger;

    public function getProviderClassName(): string
    {
        return SteamChartsProvider::class;
    }

    public function fetch(ImportConnector $connector): \Iterator
    {
        $currentPage = 1;

        do {
            $this->logger && $this->logger->debug("Downloading page $currentPage...");

            $pages[] = async(fn () => SteamChartsParser::parseChart($connector->fetch(
                new HttpDataSource(self::BASE_URL . $currentPage)
            )->getBody()));
        } while (++$currentPage <= self::PAGES);

        foreach (Future::iterate($pages) as $page) {
            yield from $page->await();
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
