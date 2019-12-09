<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use Amp\Iterator;
use Amp\Producer;
use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\AsyncHttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\AsyncResource;

/**
 * Gets the 30-day player history for the specified app.
 */
class GetPlayersHistory implements AsyncResource
{
    private const URL = 'https://steamcharts.com/app/%s/chart-data.json';

    private $appId;

    public function __construct(int $appId)
    {
        $this->appId = $appId;
    }

    public function getProviderClassName(): string
    {
        return SteamChartsProvider::class;
    }

    public function fetchAsync(ImportConnector $connector): Iterator
    {
        return new Producer(function (\Closure $emit) use ($connector): \Generator {
            $response = yield $connector->fetchAsync(new AsyncHttpDataSource(sprintf(self::URL, $this->appId)));

            $json = \json_decode((string)$response, true);

            for ($item = end($json); $item !== false; $item = prev($json)) {
                yield $emit([
                    'date' => new \DateTimeImmutable('@' . $item[0] / 1000),
                    'players' => $item[1],
                ]);
            }
        });
    }
}
