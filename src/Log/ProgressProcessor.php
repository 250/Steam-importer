<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Log;

final class ProgressProcessor
{
    public function __invoke(array $record): array
    {
        $count = $record['context']['count'] ?? null;
        $total = $record['context']['total'] ?? null;

        if (isset($count, $total)) {
            $percent = ($count / $total) * 100 | 0;

            $record['message'] = "$count/$total ($percent%) $record[message]";
        }

        return $record;
    }
}
