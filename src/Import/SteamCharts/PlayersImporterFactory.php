<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\PorterFactory;
use ScriptFUSION\Steam250\Shared\Log\LoggerFactory;

final class PlayersImporterFactory
{
    public function create(bool $verbose): PlayersImporter
    {
        return new PlayersImporter(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create(),
            (new LoggerFactory)->create('Players import', $verbose)
        );
    }
}
