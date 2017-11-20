<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Stitch;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StitchCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('stitch')
            ->setDescription('Stitch database chunks back together.')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to database chunks.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        (new DatabaseStitcherFactory)->create($input->getArgument('path'))->stitch();

        return 0;
    }
}
