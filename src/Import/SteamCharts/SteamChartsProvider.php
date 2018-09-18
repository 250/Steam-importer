<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use ScriptFUSION\Porter\Connector\AsyncConnector;
use ScriptFUSION\Porter\Net\Http\AsyncHttpConnector;
use ScriptFUSION\Porter\Provider\AsyncProvider;

final class SteamChartsProvider implements AsyncProvider
{
    public function getAsyncConnector(): AsyncConnector
    {
        return new AsyncHttpConnector;
    }
}
