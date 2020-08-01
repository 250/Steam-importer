<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Club250AppListCommand extends Command
{
    const URL = 'https://club.steam250.com/api/applist';

    protected function configure(): void
    {
        $this
            ->setName('c250-applist')
            ->setDescription('Import full list of Steam app IDs in text format from Club 250.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $content = file_get_contents(self::URL);

        if ($content === false) {
            throw new \RuntimeException('Failed to download app list.');
        }

        echo $content;

        return 0;
    }
}
