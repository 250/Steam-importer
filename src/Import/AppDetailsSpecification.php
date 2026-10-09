<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Porter\Connector\Recoverable\ExponentialAsyncDelayRecoverableExceptionHandler;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Provider\Steam\Resource\ScrapeAppDetails;
use ScriptFUSION\Porter\Provider\Steam\Resource\StoreSession;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;
use ScriptFUSION\Steam250\Shared\Mapping\AppDetailsMapping;

final class AppDetailsSpecification extends Import
{
    public function __construct(int $appId, ?StoreSession $storeSession = null)
    {
        parent::__construct(new ScrapeAppDetails($appId, $storeSession));

        $this->addTransformer(new MappingTransformer(new AppDetailsMapping($appId)));
        $this->setRecoverableExceptionHandler(new ExponentialAsyncDelayRecoverableExceptionHandler(500));

        $this->setMaxFetchAttempts(10);
    }
}
