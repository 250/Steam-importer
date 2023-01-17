<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamSpy;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Provider\Resource\StaticResource;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;

final class SteamSpySpecification extends Import
{
    public function __construct(string $steamSpyDataPath)
    {
        parent::__construct(new StaticResource(new \ArrayIterator(
            \json_decode(file_get_contents($steamSpyDataPath), true)
        )));

        $this->addTransformer(
            new MappingTransformer(
                new AnonymousMapping([
                    'owners' => new Copy('owners'),
                ])
            )
        );
    }
}
