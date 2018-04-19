<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

final class ImportAsyncFactory
{
    public function create(string $appListPath, int $chunks, int $chunkIndex, bool $verbose): Importer
    {
        $factory = new ImporterFactory;
        $importer = $factory->create($appListPath, $chunks, $chunkIndex, $verbose);
        $importer->setAppDetailsImporter(new AppDetailsImporterAsync);

        return $importer;
    }
}
