<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;
use ScriptFUSION\Porter\Provider\Resource\SingleRecordResource;

final class GetClub250Trending implements ProviderResource, SingleRecordResource
{
    private const URL = 'https://api.steam250.com/ranking/new-and-trending';

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
            (new HttpDataSource(self::URL))
                ->addHeader('authorization', "Bearer $this->apiToken")
        );

        yield array_filter(explode("\n", (string)$response));
    }
}
