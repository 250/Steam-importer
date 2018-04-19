<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Log;

/**
 * Translates all log levels into exactly four characters.
 */
final class ShortLevelNameProcessor
{
    private const LEVELS = [
        'DEBUG'     => 'dbug',
        'INFO'      => 'info',
        'NOTICE'    => 'note',
        'WARNING'   => 'Warn',
        'ERROR'     => 'EROR',
        'CRITICAL'  => 'CRIT',
        'ALERT'     => 'ALRT',
        'EMERGENCY' => 'EMRG',
    ];

    public function __invoke(array $record): array
    {
        $record['level_name'] = self::LEVELS[$record['level_name']];

        return $record;
    }
}
