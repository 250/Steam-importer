<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Steam;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TopSellersCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('import-top-sellers')
            ->setDescription('Import the top 100 globally top selling Steam apps.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return (new TopSellersImporterFactory)->create($output->isVeryVerbose())->import()
            ? self::SUCCESS
            : self::FAILURE;
    }
}
