<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Steam250\Log\LoggerFactory;
use ScriptFUSION\Steam250\Storage\Storage\ReadWriteStorageFactory;

final class StoriesRefresherFactory
{
    public function create(): StoriesRefresher
    {
        return new StoriesRefresher(
            (new ReadWriteStorageFactory)->create(),
            new StoriesImporterFactory,
            (new LoggerFactory)->create('Stories refresh', false),
        );
    }
}
