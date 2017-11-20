<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use ScriptFUSION\Steam250\Import\ImportCommand;
use ScriptFUSION\Steam250\Stitch\StitchCommand;

final class Application
{
    private $app;

    public function __construct()
    {
        $this->app = $app = new \Symfony\Component\Console\Application;

        $app->addCommands([
            new ApplistCommand,
            new ImportCommand,
            new StitchCommand,
        ]);
    }

    public function start(): int
    {
        return $this->app->run();
    }
}
