<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Patreon\Strategy;

use ScriptFUSION\Steam250\MapperStrategy\ContextSwap;
use ScriptFUSION\Steam250\MapperStrategy\CopyAll;
use ScriptFUSION\Steam250\MapperStrategy\Find;

class RelationshipStrategy extends ContextSwap
{
    /**
     * @param string $name Relationship name.
     * @param string|null $type Relationship type.
     */
    public function __construct(string $name, string $type = null)
    {
        $type = $type ?? $name;

        parent::__construct(
            new Find(
                new CopyAll,
                function (array $resource, $key, $id) use ($type): bool {
                    return $resource['type'] === $type && $resource['id'] === $id;
                }
            ),
            new RelationshipIdStrategy($name)
        );
    }
}
