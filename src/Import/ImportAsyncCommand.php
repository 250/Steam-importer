<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

class ImportAsyncCommand extends ImportCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('import-async')
            ->setDescription('Import app data asynchronously, for each Steam app, into a database.')
        ;
    }

    protected function createFactory()
    {
        return new ImportAsyncFactory;
    }
}
