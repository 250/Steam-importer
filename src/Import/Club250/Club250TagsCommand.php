<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Club250TagsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('c250-tags')
            ->setDescription('Import full list of tags and tag categories from Club 250.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        (new TagsImporterFactory)->create()->import();

        return self::SUCCESS;
    }
}
