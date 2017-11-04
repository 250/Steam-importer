<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Transformer\Collection;

use ScriptFUSION\Porter\Collection\CountableRecordsTrait;
use ScriptFUSION\Porter\Collection\RecordCollection;

class ChunkedRecords extends RecordCollection implements \Countable
{
    use CountableRecordsTrait;

    public function __construct(\Iterator $records, int $count, RecordCollection $previousCollection = null)
    {
        parent::__construct($records, $previousCollection);

        $this->setCount($count);
    }
}
