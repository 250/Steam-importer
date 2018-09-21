<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use ScriptFUSION\Porter\Connector\Recoverable\StatelessRecoverableExceptionHandler;
use ScriptFUSION\Porter\Net\Http\HttpServerException;
use ScriptFUSION\Porter\Specification\AsyncImportSpecification;

class GetPlayersHistorySpecification extends AsyncImportSpecification
{
    public function __construct(GetPlayersHistory $resource)
    {
        parent::__construct($resource);

        $this->setRecoverableExceptionHandler(new StatelessRecoverableExceptionHandler(
            \Closure::fromCallable([$this, 'handle'])
        ));
    }

    private static function handle(\Exception $exception): void
    {
        if ($exception instanceof HttpServerException
            && $exception->getCode() === 404
        ) {
            // Treat 404 errors as unrecoverable.
            throw new GameUnavailableException('Server returned HTTP 404.', 0, $exception);
        }
    }
}
