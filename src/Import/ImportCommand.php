<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('import')
            ->setDescription('Import app data for each Steam app into a database.')
            ->addArgument('applist', InputArgument::REQUIRED, 'Path to Steam app list in JSON format.')
            ->addOption(
                'chunks',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Number of chunks to split import into.',
                Importer::DEFAULT_CHUNKS
            )
            ->addOption(
                'chunk-index',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Chunk index of this job (1 to total chunks).',
                Importer::DEFAULT_CHUNK_INDEX
            )
            ->addOption('lite', null, null, 'Do not insert invalid apps or apps with no reviews.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $importer = (new ImporterFactory)->create(
            $input->getArgument('applist'),
            (int)$input->getOption('chunks'),
            (int)$input->getOption('chunk-index'),
            $input->getOption('verbose')
        );
        $importer->setLite($input->getOption('lite'));

        $importer->import();

        return 0;
    }
}
