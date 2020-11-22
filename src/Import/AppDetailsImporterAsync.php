<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Promise;
use ScriptFUSION\Async\Throttle\Throttle;
use ScriptFUSION\Porter\Porter;

final class AppDetailsImporterAsync implements AppDetailsImporter
{
    public function __invoke(Porter $porter, int $appId, Throttle $throttle): Promise
    {
        return $porter->importOneAsync((new AsyncAppDetailsSpecification($appId))->setThrottle($throttle));
    }
}
