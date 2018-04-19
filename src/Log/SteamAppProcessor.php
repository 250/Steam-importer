<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Log;

final class SteamAppProcessor
{
    public function __invoke(array $record): array
    {
        $app = $record['context']['app'] ?? null;

        if ($app) {
            $record['message'] = str_replace('%app%', "#$app[id] $app[name]", $record['message']);
        }

        return $record;
    }
}
