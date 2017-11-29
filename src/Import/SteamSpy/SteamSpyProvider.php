<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamSpy;

use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Provider\Provider;

class SteamSpyProvider implements Provider
{
    public function getConnector()
    {
        return new HttpConnector;
    }
}
