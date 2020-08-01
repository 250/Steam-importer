<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Patreon;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static self STEAM
 * @method static self CLUB250
 */
final class ApplistFormat extends AbstractEnumeration
{
    public const STEAM = 'STEAM';
    public const CLUB250 = 'CLUB250';
}
