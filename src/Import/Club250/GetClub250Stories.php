<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;

final class GetClub250Stories implements ProviderResource
{
    private const URL = 'https://api.steam250.com/stories';

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

        yield from json_decode((string)$response, true, flags: JSON_THROW_ON_ERROR);
    }
}
