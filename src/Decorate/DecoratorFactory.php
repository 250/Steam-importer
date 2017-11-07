<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Decorate;

use Monolog\Logger;
use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\PorterFactory;

final class DecoratorFactory
{
    public function create(string $dbPath): Decorator
    {
        return new Decorator(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create("$dbPath/steam.sqlite"),
            new Logger('Decorate')
        );
    }
}
