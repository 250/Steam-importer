<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Steam250\Import\SteamSpy\SteamSpyResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SteamSpyCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('steam-spy')
            ->setDescription('Import full list of Steam apps in JSON format.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $records = (new PorterFactory())->create()->import(new ImportSpecification(new SteamSpyResource));

        echo json_encode(iterator_to_array($records));

        return 0;
    }
}
