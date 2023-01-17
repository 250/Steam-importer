<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Provider\Provider;

final class SteamChartsProvider implements Provider
{
    private HttpConnector $connector;

    public function __construct()
    {
        $this->connector = new HttpConnector();
    }

    public function getConnector(): Connector
    {
        return $this->connector;
    }
}
