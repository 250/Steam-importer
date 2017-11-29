<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;
use ScriptFUSION\Steam250\Import\SteamSpy\SpeamSpyResource;

class PlayersSpecification extends ImportSpecification
{
    public function __construct()
    {
        parent::__construct(new SpeamSpyResource);

        $this->addTransformer(
            new MappingTransformer(
                new AnonymousMapping(
                    new Copy('players_forever')
                )
            )
        );
    }
}
