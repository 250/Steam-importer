<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Steam;

use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\Log\LoggerFactory;
use ScriptFUSION\Steam250\PorterFactory;

final class TopSellersImporterFactory
{
    public function create(bool $verbose): TopSellersImporter
    {
        return new TopSellersImporter(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create(),
            (new LoggerFactory)->create('Global top sellers', $verbose)
        );
    }
}
