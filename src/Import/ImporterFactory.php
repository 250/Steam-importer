<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Porter\Provider\Steam\Resource\StoreSession;
use ScriptFUSION\Steam250\Database\DatabaseFactory;
use ScriptFUSION\Steam250\Log\LoggerFactory;
use ScriptFUSION\Steam250\PorterFactory;

final class ImporterFactory
{
    public function create(
        string $appListPath,
        int $chunks,
        int $chunkIndex,
        bool $overwrite,
        bool $verbose,
        ?string $steamUser = null,
        ?string $steamPass = null
    ): Importer {
        $extension = 'sqlite';
        $chunks && $extension .= ".p$chunkIndex";

        $importer = new Importer(
            $porter = (new PorterFactory)->create(),
            (new DatabaseFactory)->create("steam.$extension", $overwrite),
            (new LoggerFactory)->create('Import', $verbose),
            $appListPath,
            isset($steamUser, $steamPass) ? StoreSession::create($porter, $steamUser, $steamPass) : null
        );
        $importer->setChunks($chunks);
        $importer->setChunkIndex($chunkIndex);

        return $importer;
    }
}
