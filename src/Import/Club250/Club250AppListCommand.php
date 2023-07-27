<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Steam250\PorterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Club250AppListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('c250-applist')
            ->setDescription('Import full list of Steam app IDs in text format from Club 250.')
            ->addArgument('api-token', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        echo (new PorterFactory())->create()->importOne(new Import(
            new GetClub250AppList($input->getArgument('api-token'))
        ))[0];

        return self::SUCCESS;
    }
}
