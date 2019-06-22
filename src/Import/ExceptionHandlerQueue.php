<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Promise;
use ScriptFUSION\Porter\Connector\Recoverable\RecoverableException;
use ScriptFUSION\Porter\Connector\Recoverable\RecoverableExceptionHandler;

class ExceptionHandlerQueue implements RecoverableExceptionHandler
{
    /**
     * @var RecoverableExceptionHandler[]
     */
    private $handlers;

    public function __construct(RecoverableExceptionHandler ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public function initialize(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->initialize();
        }
    }

    public function __invoke(RecoverableException $exception): ?Promise
    {
        return \Amp\call(function () use ($exception): \Generator {
            foreach ($this->handlers as $handler) {
                if ($promise = $handler($exception)) {
                    yield $promise;
                }
            }
        });
    }
}
