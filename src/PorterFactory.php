<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use Joomla\DI\Container;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Patreon\Connector\PatreonConnector;
use ScriptFUSION\Porter\Provider\Patreon\PatreonProvider;
use ScriptFUSION\Porter\Provider\Steam\SteamProvider;
use ScriptFUSION\Steam250\Import\SteamSpy\SteamSpyProvider;

final class PorterFactory
{
    public function create(): Porter
    {
        $porter = new Porter($container = new Container);

        $container->set(SteamProvider::class, new SteamProvider);
        $container->set(SteamSpyProvider::class, new SteamSpyProvider);
        $container->set(PatreonProvider::class, function () {
            return new PatreonProvider(new PatreonConnector($_SERVER['PATREON_API_KEY']));
        });

        return $porter;
    }
}
