<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Patreon\Strategy;

use ScriptFUSION\Mapper\Strategy\Copy;

class RelationshipIdStrategy extends Copy
{
    public function __construct(string $type)
    {
        parent::__construct(['relationships', $type, 'data', 'id']);
    }
}
