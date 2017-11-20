<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Callback;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Porter\Provider\Steam\Resource\ScrapeAppDetails;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;

class GameReviewsSpecification extends ImportSpecification
{
    public function __construct(int $appId)
    {
        parent::__construct(new ScrapeAppDetails($appId));

        $this->addTransformer(
            new MappingTransformer(
                new AnonymousMapping([
                    'app_name' => new Copy('name'),
                    'app_type' => new Copy('type'),
                    'release_date' => new Callback(
                        function (array $data): ?int {
                            return $data['release_date'] ? $data['release_date']->getTimestamp() : null;
                        }
                    ),
                    'genre' => new Copy('tags->0'),
                    'positive_reviews' => new Copy('positive_reviews'),
                    'negative_reviews' => new Copy('negative_reviews'),
                    'total_reviews' => new Callback(
                        function (array $data): int {
                            return $data['positive_reviews'] + $data['negative_reviews'];
                        }
                    ),
                ])
            )
        );
    }
}
