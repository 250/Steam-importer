<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use GetOpt\ArgumentException;
use GetOpt\Command;
use GetOpt\GetOpt;
use GetOpt\Operand;
use ScriptFUSION\Porter\Provider\Steam\Resource\GetAppList;
use ScriptFUSION\Steam250\Decorate\DecoratorFactory;
use ScriptFUSION\Steam250\Generate\GeneratorFactory;
use ScriptFUSION\Steam250\Import\Importer;
use ScriptFUSION\Steam250\Import\ImporterFactory;

final class Cli
{
    private $cli;

    public function __construct()
    {
        $this->cli = (new GetOpt([
            ['?', 'help', GetOpt::NO_ARGUMENT, 'Show this help.'],
        ]))->addCommands([
            (new Command('import-apps', [$this, 'importApps']))
                ->setShortDescription('Import full list of Steam apps in JSON format.'),

            (new Command('import-reviews', [$this, 'importReviews']))
                ->setShortDescription('Import reviews for each Steam app into database.')
                ->addOptions([
                    [
                        'c',
                        'chunks',
                        GetOpt::REQUIRED_ARGUMENT,
                        'Number of chunks to split import into.',
                        Importer::DEFAULT_CHUNKS,
                    ],
                    [
                        'i',
                        'chunk-index',
                        GetOpt::REQUIRED_ARGUMENT,
                        'Chunk index of this job (1-total chunks).',
                        Importer::DEFAULT_CHUNK_INDEX,
                    ],
                ])
                ->addOperand(new Operand('appListPath', Operand::REQUIRED))
                    ->setShortDescription('Path to Steam app list in JSON format.')
            ,

            (new Command('decorate', [$this, 'decorate']))
                ->setShortDescription('Decorate each Steam game with additional information in database.'),

            (new Command('generate', [$this, 'generate']))
                ->setShortDescription('Generate Steam Top 250 site content from database.'),
        ]);
    }

    public function run(): void
    {
        try {
            $this->cli->process();
        } catch (ArgumentException $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL);
            echo PHP_EOL . $this->cli->getHelpText();

            return;
        }

        if ($this->cli->getOption('help') || !$command = $this->cli->getCommand()) {
            echo $this->cli->getHelpText();

            return;
        }

        $command->handler()($command);
    }

    public function importApps(): void
    {
        echo file_get_contents(GetAppList::getUrl());
    }

    public function importReviews(Command $command): void
    {
        (new ImporterFactory)->create(
            $command->getOperand('appListPath')->value(),
            +$command->getOption('chunks')->value(),
            +$command->getOption('chunk-index')->value()
        )->import();
    }

    public function decorate(): void
    {
        (new DecoratorFactory)->create()->decorate();
    }

    public function generate(): void
    {
        (new GeneratorFactory)->create()->generate();
    }
}
