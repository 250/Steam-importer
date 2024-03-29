<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;
use ScriptFUSION\Steam250\Resource\StaticSteamAppList;
use ScriptFUSION\Steam250\Transformer\ChunkingTransformer;

final class SteamAppListSpecification extends Import
{
    public function __construct(string $appListPath, int $chunks, int $chunkIndex)
    {
        parent::__construct(new StaticSteamAppList($appListPath));

        $this->addTransformers([
            new ChunkingTransformer($chunks, $chunkIndex),
            new MappingTransformer(
                new AnonymousMapping([
                    'id' => new Copy('appid'),
                    'name' => new Copy('name'),
                ])
            ),
        ]);
    }
}
