<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Patreon;

use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\PorterFactory;
use ScriptFUSION\Steam250\Shared\Log\LoggerFactory;

final class PatreonImporterFactory
{
    public function create(bool $verbose): PatronImporter
    {
        return new PatronImporter(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create(),
            (new LoggerFactory)->create('Patreon import', $verbose)
        );
    }
}
