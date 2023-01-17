<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PlayersImportCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('players-import')
            ->setDescription('Import peak players data for the top 1000 most played games.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $importer = (new PlayersImporterFactory)->create($output->isVeryVerbose());

        return $importer->import() ? self::SUCCESS : self::FAILURE;
    }
}
