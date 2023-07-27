<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Steam250\Resource\StaticClub250AppList;
use ScriptFUSION\Steam250\Transformer\ChunkingTransformer;

final class Club250AppListSpecification extends Import
{
    public function __construct(string $appListPath, int $chunks, int $chunkIndex)
    {
        parent::__construct(new StaticClub250AppList($appListPath));

        $this->addTransformers([
            new ChunkingTransformer($chunks, $chunkIndex),
        ]);
    }
}
