<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Connector\CachingConnector;
use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Provider\Provider;

final class Club250Provider implements Provider
{
    private Connector $connector;

    public function __construct()
    {
        $this->connector = new CachingConnector(new HttpConnector());
    }

    public function getConnector(): Connector
    {
        return $this->connector;
    }
}
