<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Transformer;

use ScriptFUSION\Porter\Collection\RecordCollection;
use ScriptFUSION\Porter\Transform\Transformer;
use ScriptFUSION\Steam250\Transformer\Collection\ChunkedRecords;

class ChunkingTransformer implements Transformer
{
    private $chunks;

    private $chunkIndex;

    /**
     * @param int $chunks Number of chunks to split collection into. If zero, chunking is disabled.
     * @param int $chunkIndex One-based chunk index.
     */
    public function __construct(int $chunks, int $chunkIndex)
    {
        // TODO: Validation.
        $this->chunks = $chunks;
        $this->chunkIndex = $chunkIndex;
    }

    public function transform(RecordCollection $records, $context): RecordCollection
    {
        if (!$records instanceof \Countable) {
            // TODO: Consider allowing passing total count to constructor if caller knows it somehow.
            throw new \InvalidArgumentException('Non-countable records currently unsupported.');
        }

        $chunkSize = $this->chunks > 0 ? count($records) / $this->chunks : count($records);
        $start = ceil($chunkSize * ($this->chunkIndex - 1));
        // TODO: Fix this. ceil() is causing chunk overlaps.
        $end = ceil($start + $chunkSize);

        return new ChunkedRecords(
            (function () use ($records, $start, $end): \Generator {
                $i = 0;
                foreach ($records as $record) {
                    if ($i >= $start && $i < $end) {
                        yield $record;
                    }
                    ++$i;
                }
            })(),
            // TODO: Figure out how to calculate the correct size for the last chunk.
            $chunkSize | 0,
            $records
        );
    }
}
