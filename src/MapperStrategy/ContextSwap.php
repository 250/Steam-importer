<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\MapperStrategy;

use ScriptFUSION\Mapper\Strategy\Delegate;

// TODO: Move to Mapper.
class ContextSwap extends Delegate
{
    private $newContext;

    public function __construct($expression, $newContext = null)
    {
        parent::__construct($expression);

        $this->newContext = $newContext;
    }

    public function __invoke($data, $context = null)
    {
        return parent::__invoke(
            $context,
            $this->newContext === null
                ? $data
                : $this->delegate($this->newContext, $data, $context)
        );
    }
}
