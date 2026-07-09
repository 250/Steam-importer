<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use Joomla\DI\Container;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\SteamProvider;
use ScriptFUSION\Steam250\Import\Club250\Club250Provider;
use ScriptFUSION\Steam250\Import\SteamCharts\SteamChartsProvider;
final class PorterFactory
{
    public function create(): Porter
    {
        $porter = new Porter($container = new Container);

        $container->set(SteamProvider::class, new SteamProvider);
        $container->set(SteamChartsProvider::class, new SteamChartsProvider);
        $container->set(Club250Provider::class, new Club250Provider);

        return $porter;
    }
}
