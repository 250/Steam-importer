<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Mapper\Strategy\Merge;
use ScriptFUSION\Mapper\Strategy\TakeFirst;
use ScriptFUSION\Mapper\Strategy\TryCatch;
use ScriptFUSION\Porter\Provider\Steam\Resource\ApiResponseException;
use ScriptFUSION\Porter\Provider\Steam\Resource\GetAppList;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\FilterTransformer;
use ScriptFUSION\Porter\Transform\Mapping\Mapper\Strategy\SubImport;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;

class GameReviewsListImportSpecification extends ImportSpecification
{
    public function __construct()
    {
        parent::__construct(new GetAppList);

        $this->addTransformers([
            new MappingTransformer(
                new AnonymousMapping(
                    new Merge(
                        [
                            'id' => new Copy('appid'),
                            'game_name' => new Copy('name'),
                        ],
                        new TryCatch(
                            new TakeFirst(
                                new SubImport(
                                    function (array $data): ImportSpecification {
                                        return new GameReviewImportSpecification($data['appid']);
                                    }
                                )
                            ),
                            function (\Exception $exception): void {
                                if (!$exception instanceof ApiResponseException) {
                                    throw $exception;
                                }
                            },
                            null
                        )
                    )
                )
            ),
            new FilterTransformer(function (array $data): bool {
                return isset($data['total_reviews']) && $data['total_reviews'] > 0;
            }),
        ]);
    }
}
