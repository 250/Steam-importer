<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\SteamProvider;
use Simply\Container\Container;

final class PorterFactory
{
    public function create(): Porter
    {
        $porter = new Porter($container = new Container);

        $container[SteamProvider::class] = new SteamProvider;

        return $porter;
    }
}
