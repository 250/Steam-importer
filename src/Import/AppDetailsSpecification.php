<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Porter\Connector\Recoverable\StatelessRecoverableExceptionHandler;
use ScriptFUSION\Porter\Net\Http\HttpServerException;
use ScriptFUSION\Porter\Provider\Steam\Resource\ScrapeAppDetails;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;
use ScriptFUSION\Steam250\Import\Mapping\AppDetailsMapping;

class AppDetailsSpecification extends ImportSpecification
{
    public function __construct(int $appId)
    {
        parent::__construct(new ScrapeAppDetails($appId));

        $this->addTransformer(new MappingTransformer(new AppDetailsMapping));
        $this->setRecoverableExceptionHandler(
            new StatelessRecoverableExceptionHandler(
                static function (\Exception $exception) {
                    if ($exception instanceof HttpServerException
                        && $exception->getCode() === 500
                        && $exception->getResponse()->getBody() === ''
                    ) {
                        /* Treat this specific 500 error as unrecoverable.
                           TODO: Consider only treating as unrecoverable if it reaches retry limit.
                           TODO: Expose retry count callback parameter in Retry library. */
                        throw new FatalServerException('Server returned HTTP 500 and empty body.', 0, $exception);
                    }
                }
            )
        );
    }
}
