<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Porter\Provider\Steam\Resource\ScrapeAppDetails;
use ScriptFUSION\Porter\Specification\AsyncImportSpecification;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;
use ScriptFUSION\Steam250\Import\Mapping\AppDetailsMapping;

class AsyncAppDetailsSpecification extends AsyncImportSpecification
{
    public function __construct(int $appId)
    {
        parent::__construct(new ScrapeAppDetails($appId));

        $this->addTransformer(new MappingTransformer(new AppDetailsMapping));
    }
}
