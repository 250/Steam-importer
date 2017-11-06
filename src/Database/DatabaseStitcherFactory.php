<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Monolog\Logger;

final class DatabaseStitcherFactory
{
    public function create(string $path): DatabaseStitcher
    {
        return new DatabaseStitcher(
            (new DatabaseFactory)->create("$path/steam.sqlite"),
            new Logger('Stitch')
        );
    }
}
