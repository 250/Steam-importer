<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Porter\Connector\Recoverable\StatelessRecoverableExceptionHandler;
use ScriptFUSION\Porter\Net\Http\HttpServerException;

final class AppDetailsImportExceptionHandler extends StatelessRecoverableExceptionHandler
{
    public function __construct()
    {
        parent::__construct(\Closure::fromCallable([$this, 'handle']));
    }

    private static function handle(\Exception $exception): void
    {
        if ($exception instanceof HttpServerException
            && $exception->getCode() === 500
        ) {
            /* Treat 500 errors as unrecoverable.
               TODO: Consider only treating as unrecoverable if it reaches retry limit.
               TODO: Expose retry count callback parameter in Retry library. */
            throw new ServerFatalException('Server returned HTTP 500.', 0, $exception);
        }
    }
}
