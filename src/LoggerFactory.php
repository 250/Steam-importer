<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public function create(string $id, bool $verbose): LoggerInterface
    {
        return new Logger($id, [new StreamHandler(STDERR, $verbose ? Logger::DEBUG : Logger::INFO)]);
    }
}
