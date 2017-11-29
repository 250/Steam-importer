<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamSpy;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;

class SpeamSpyResource implements ProviderResource
{
    public function getProviderClassName(): string
    {
        return SteamSpyProvider::class;
    }

    public function fetch(ImportConnector $connector, EncapsulatedOptions $options = null): \Iterator
    {
        foreach (\json_decode((string)$connector->fetch('https://steamspy.com/api.php?request=all'), true) as
        $id => $data) {
            yield $id => $data;
        }
    }
}
