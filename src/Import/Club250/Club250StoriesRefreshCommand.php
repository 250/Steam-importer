<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Club250StoriesRefreshCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('c250-stories-refresh')
            ->setDescription('Download a database snapshot, refresh its stories from Club 250 and upload it back.')
            ->addArgument('api-token', InputArgument::REQUIRED, 'Club 250 API token.')
            ->addArgument(
                'snapshot',
                InputArgument::OPTIONAL,
                'Storage filespec of the snapshot to refresh, e.g. "202609/24/A3100/steam.sqlite" (defaults to latest).'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new StoriesRefresherFactory)->create()->refresh(
            $input->getArgument('api-token'),
            $input->getArgument('snapshot')
        );

        return self::SUCCESS;
    }
}
