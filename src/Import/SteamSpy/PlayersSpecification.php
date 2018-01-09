<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamSpy;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;

class PlayersSpecification extends ImportSpecification
{
    public function __construct()
    {
        parent::__construct(new SpeamSpyResource);

        $this->addTransformer(
            new MappingTransformer(
                new AnonymousMapping([
                    'players' => new Copy('players_forever'),
                    'players_2w' => new Copy('players_2weeks'),
                ])
            )
        );
    }
}
