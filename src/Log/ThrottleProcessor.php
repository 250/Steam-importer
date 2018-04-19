<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Log;

use ScriptFUSION\Steam250\Import\Throttle;

final class ThrottleProcessor
{
    public function __invoke(array $record): array
    {
        $throttle = $record['context']['throttle'] ?? null;

        if ($throttle instanceof Throttle) {
            $record['message'] .= " AR: {$throttle->getActive()}";
        }

        return $record;
    }
}
