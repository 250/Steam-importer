<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Stitch;

use Monolog\Logger;
use ScriptFUSION\Steam250\Database\DatabaseFactory;

final class DatabaseStitcherFactory
{
    public function create(string $dbPath): DatabaseStitcher
    {
        return new DatabaseStitcher(
            (new DatabaseFactory)->create("$dbPath/steam.sqlite"),
            new Logger('Stitch')
        );
    }
}
