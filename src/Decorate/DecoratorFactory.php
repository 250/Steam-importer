<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Decorate;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\PorterFactory;
use ScriptFUSION\Top250\Shared\Algorithm;

final class DecoratorFactory
{
    public function create(string $dbPath, Algorithm $algorithm, float $weight): Decorator
    {
        return new Decorator(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create($dbPath),
            (new Logger('Decorate'))->pushHandler(new StreamHandler(STDERR, Logger::INFO)),
            $algorithm,
            $weight
        );
    }
}
