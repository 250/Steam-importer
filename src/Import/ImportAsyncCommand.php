<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportAsyncCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('async')
            ->setDescription('TODO') # TODO
            ->addArgument('applist', InputArgument::REQUIRED, 'Path to Steam app list in JSON format.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $importer = (new ImportAsyncFactory)->create();

        return (int)!$importer->import($input->getArgument('applist'));
    }
}
