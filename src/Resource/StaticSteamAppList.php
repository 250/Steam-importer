<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Resource;

use ScriptFUSION\Porter\Provider\Resource\StaticResource;

final class StaticSteamAppList extends StaticResource
{
    public function __construct(string $path)
    {
        parent::__construct(new \ArrayIterator(\json_decode(file_get_contents($path), true)['applist']['apps']));
    }
}
