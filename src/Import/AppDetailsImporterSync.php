<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Promise;
use Amp\Success;
use ScriptFUSION\Porter\Porter;

final class AppDetailsImporterSync implements AppDetailsImporter
{
    public function __invoke(Porter $porter, int $appId): Promise
    {
        return new Success($porter->importOne(new AppDetailsSpecification($appId)));
    }
}
