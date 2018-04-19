<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public function create(string $id, bool $verbose): LoggerInterface
    {
        return new Logger(
            $id,
            [
                (new StreamHandler(STDERR, $verbose ? Logger::DEBUG : Logger::INFO))
                    ->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message%\n")),
            ],
            [
                new ShortLevelNameProcessor,
                new ProgressProcessor,
                new SteamAppProcessor,
                new ThrottleProcessor,
            ]
        );
    }
}
