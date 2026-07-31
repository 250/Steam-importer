<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Club250StoriesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('c250-stories')
            ->setDescription('Import stories from Club 250.')
            ->addArgument('api-token', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        new StoriesImporterFactory()->create()->import($input->getArgument('api-token'));

        return self::SUCCESS;
    }
}
