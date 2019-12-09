<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use Amp\Delayed;
use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\AsyncHttpDataSource;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSION\Porter\Provider\Resource\AsyncResource;

class GetCurrentPlayers implements AsyncResource
{
    private const BASE_URL = 'https://steamcharts.com/top/p.';

    private const PAGES = 80;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function getProviderClassName(): string
    {
        return SteamChartsProvider::class;
    }

    public function fetchAsync(ImportConnector $connector): Iterator
    {
        return new Producer(function (\Closure $emit) use ($connector): \Generator {
            // Stop Coroutine wrapper at this point.
            yield new Delayed(0);

            $currentPage = 1;

            do {
                $this->logger && $this->logger->debug("Downloading page $currentPage...");

                $responses[] = self::emitParsedBody($emit, $connector->fetchAsync(
                    new AsyncHttpDataSource(self::BASE_URL . $currentPage)
                ));
            } while (++$currentPage <= self::PAGES);

            yield $responses;
        });
    }

    private static function emitParsedBody(\Closure $emit, Promise $responsePromise): Promise
    {
        return \Amp\call(static function () use ($emit, $responsePromise): \Generator {
            /** @var HttpResponse $response */
            $response = yield $responsePromise;

            foreach (SteamChartsParser::parseChart($response->getBody()) as $game) {
                yield $emit($game);
            }
        });
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
