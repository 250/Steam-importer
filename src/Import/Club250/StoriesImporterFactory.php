<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\Log\LoggerFactory;
use ScriptFUSION\Steam250\PorterFactory;

final class StoriesImporterFactory
{
    public function create(string $databasePath = 'steam.sqlite'): StoriesImporter
    {
        return new StoriesImporter(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create($databasePath),
            (new LoggerFactory)->create('Stories Importer', false),
        );
    }
}
