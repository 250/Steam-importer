<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\MapperStrategy;

use ScriptFUSION\Mapper\Mapping;
use ScriptFUSION\Mapper\Strategy\Delegate;
use ScriptFUSION\Mapper\Strategy\Strategy;

// TODO: Move to Mapper.
class Find extends Delegate
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param Strategy|Mapping|array|mixed $expression Expression.
     * @param callable $callback Callback function that receives the current value as its first argument, the current
     *     key as its second argument and context as its third argument.
     */
    public function __construct($expression, callable $callback)
    {
        parent::__construct($expression);

        $this->callback = $callback;
    }

    public function __invoke($data, $context = null)
    {
        if (!\is_array($data = parent::__invoke($data, $context))) {
            return null;
        }

        foreach ($data as $key => $datum) {
            if (($this->callback)($datum, $key, $context)) {
                return $datum;
            }
        }
    }
}
