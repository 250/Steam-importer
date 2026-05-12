<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;

final class GetClub250Trending implements ProviderResource
{
    private const URL = 'https://api.steam250.com/ranking/trending-now';

    public function __construct(private readonly string $apiToken)
    {
    }

    public function getProviderClassName(): string
    {
        return Club250Provider::class;
    }

    public function fetch(ImportConnector $connector): \Iterator
    {
        $response = $connector->fetch(
            new HttpDataSource(self::URL)
                ->addHeader('authorization', "Bearer $this->apiToken")
        );

        yield from explode("\n", (string)$response)
            |> array_filter(...)
            |> (static fn ($line) => array_map(static fn ($line) => explode(' ', $line), $line))
        ;
    }
}
