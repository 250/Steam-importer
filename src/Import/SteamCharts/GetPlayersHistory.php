<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;

/**
 * Gets the 30-day player history for the specified app.
 */
final class GetPlayersHistory implements ProviderResource
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

    public function fetch(ImportConnector $connector): \Iterator
    {
        $response = $connector->fetch(new HttpDataSource(sprintf(self::URL, $this->appId)));

        $json = \json_decode((string)$response, true);

        for ($item = end($json); $item !== false; $item = prev($json)) {
            yield [
                'date' => new \DateTimeImmutable('@' . $item[0] / 1000),
                'players' => $item[1],
            ];
        }
    }
}
