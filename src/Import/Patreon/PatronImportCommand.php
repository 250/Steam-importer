<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Patreon;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PatronImportCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('patron-import')
            ->setDescription('Import Steam reviews data for each patron into a database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $importer = (new PatreonImporterFactory)->create($output->isVeryVerbose());
        $importer->import();

        return 0;
    }
}
