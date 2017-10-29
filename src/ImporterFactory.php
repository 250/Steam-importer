<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use Monolog\Logger;

final class ImporterFactory
{
    public function create()
    {
        return new Importer(new Logger('Steam 250'));
    }
}
