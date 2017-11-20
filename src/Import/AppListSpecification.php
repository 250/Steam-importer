<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;
use ScriptFUSION\Steam250\Resource\StaticSteamAppList;
use ScriptFUSION\Steam250\Transformer\ChunkingTransformer;

class AppListSpecification extends ImportSpecification
{
    public function __construct(string $appListPath, int $chunks, int $chunkIndex)
    {
        parent::__construct(new StaticSteamAppList($appListPath));

        $this->addTransformers([
            new ChunkingTransformer($chunks, $chunkIndex),
            new MappingTransformer(
                new AnonymousMapping([
                    'id' => new Copy('appid'),
                    'app_name' => new Copy('name'),
                ])
            ),
        ]);
    }
}
