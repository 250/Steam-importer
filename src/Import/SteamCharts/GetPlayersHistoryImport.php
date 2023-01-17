<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use ScriptFUSION\Porter\Connector\Recoverable\StatelessRecoverableExceptionHandler;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Net\Http\HttpServerException;

final class GetPlayersHistoryImport extends Import
{
    public function __construct(GetPlayersHistory $resource)
    {
        parent::__construct($resource);

        $this->setRecoverableExceptionHandler(new StatelessRecoverableExceptionHandler(self::handle(...)));
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
