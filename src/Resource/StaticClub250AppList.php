<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Resource;

use ScriptFUSION\Porter\Collection\CountableProviderRecords;
use ScriptFUSION\Porter\Provider\Resource\StaticResource;

final class StaticClub250AppList extends StaticResource
{
    public function __construct(string $path)
    {
        $file = new \SplFileObject($path);
        $file->setFlags($file::READ_AHEAD);

        parent::__construct(
            new CountableProviderRecords(
                (static function () use ($file): \Generator {
                    foreach ($file as $line) {
                        yield ['id' => (int)$line, 'name' => ''];
                    }
                })(),
                iterator_count($file),
                $this
            )
        );
    }
}
