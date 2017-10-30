<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Monolog\Logger;
use ScriptFUSION\Steam250\DatabaseFactory;
use ScriptFUSION\Steam250\PorterFactory;

final class ImporterFactory
{
    public function create(): Importer
    {
        return new Importer(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create(),
            new Logger('Steam 250')
        );
    }
}
