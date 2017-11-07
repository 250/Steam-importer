<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

final class Application
{
    public function start(): void
    {
        (new Cli)->run();
    }

    public static function getAppPath(string $path)
    {
        return __DIR__ . "/../$path";
    }
}
