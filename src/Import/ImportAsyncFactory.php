<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Steam250\LoggerFactory;
use ScriptFUSION\Steam250\PorterFactory;

class ImportAsyncFactory
{
    public function create(): ImportAsync
    {
        return new ImportAsync(
            (new PorterFactory)->create(),
            (new LoggerFactory)->create('Async', true)
        );
    }
}
