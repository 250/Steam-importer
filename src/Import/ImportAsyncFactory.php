<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

final class ImportAsyncFactory
{
    public function create(string $appListPath, int $chunks, int $chunkIndex, bool $overwrite, bool $verbose): Importer
    {
        $factory = new ImporterFactory;
        $importer = $factory->create($appListPath, $chunks, $chunkIndex, $overwrite, $verbose);
        $importer->setAppDetailsImporter(new AppDetailsImporterAsync);

        return $importer;
    }
}
