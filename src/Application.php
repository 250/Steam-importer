<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use ScriptFUSION\Steam250\Import\Club250\Club250AppListCommand;
use ScriptFUSION\Steam250\Import\Club250\Club250TagsCommand;
use ScriptFUSION\Steam250\Import\Club250\Club250TrendingCommand;
use ScriptFUSION\Steam250\Import\ImportCommand;
use ScriptFUSION\Steam250\Import\Patreon\PatronImportCommand;
use ScriptFUSION\Steam250\Import\SteamCharts\PlayersImportCommand;
use ScriptFUSION\Steam250\Stitch\StitchCommand;

final class Application
{
    private $app;

    public function __construct()
    {
        $this->app = $app = new \Symfony\Component\Console\Application;

        $app->addCommands([
            new SteamApplistCommand,
            new Club250AppListCommand,
            new Club250TagsCommand,
            new Club250TrendingCommand,
            new PlayersImportCommand,
            new SteamSpyCommand,
            new ImportCommand,
            new StitchCommand,
            new PatronImportCommand,
        ]);
    }

    public function start(): int
    {
        return $this->app->run();
    }
}
