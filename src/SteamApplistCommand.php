<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use ScriptFUSION\Porter\Provider\Steam\Resource\GetAppList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SteamApplistCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('applist')
            ->setDescription('Import full list of Steam apps in JSON format.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $content = file_get_contents((new GetAppList)->getUrl());

        if ($content === false) {
            throw new \RuntimeException('Failed to download app list.');
        }

        echo $content;

        return 0;
    }
}
