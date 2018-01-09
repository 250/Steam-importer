<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\MapperStrategy;

use ScriptFUSION\Mapper\Strategy\Strategy;

// TODO: Implement in Copy.
class CopyAll implements Strategy
{
    public function __invoke($data, $context = null)
    {
        return $data;
    }
}
