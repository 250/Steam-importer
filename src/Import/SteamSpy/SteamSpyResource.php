<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamSpy;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;

class SteamSpyResource implements ProviderResource
{
    public function getProviderClassName(): string
    {
        return SteamSpyProvider::class;
    }

    public function fetch(ImportConnector $connector): \Iterator
    {
        foreach (\json_decode((string)$connector->fetch(
            new HttpDataSource('https://steamspy.com/api.php?request=all')
        ), true) as $id => $data) {
            yield $id => $data;
        }
    }
}
