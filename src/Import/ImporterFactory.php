<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\PorterFactory;

final class ImporterFactory
{
    public function create(string $appListPath, int $chunks, int $chunkIndex, bool $verbose): Importer
    {
        $extension = 'sqlite';
        $chunks && $extension .= ".p$chunkIndex";

        $importer = new Importer(
            (new PorterFactory)->create(),
            (new DatabaseFactory)->create("steam.$extension"),
            new Logger('Import', [new StreamHandler(STDERR, $verbose ? Logger::DEBUG : Logger::INFO)]),
            $appListPath
        );
        $importer->setChunks($chunks);
        $importer->setChunkIndex($chunkIndex);

        return $importer;
    }
}
