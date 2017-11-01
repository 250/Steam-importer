<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use ScriptFUSION\Porter\Collection\CountableProviderRecords;
use ScriptFUSION\Porter\Collection\RecordCollection;
use ScriptFUSION\Porter\Provider\Steam\Collection\UserReviewsCollection;
use ScriptFUSION\Porter\Provider\Steam\Resource\GetUserReviewsList;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\Transformer;

class GameReviewsSpecification extends ImportSpecification
{
    public function __construct(int $appId)
    {
        parent::__construct(new GetUserReviewsList($appId));

        $this->addTransformer(
            new class implements Transformer {
                public function transform(RecordCollection $records, $context): RecordCollection
                {
                    if (!$records instanceof UserReviewsCollection) {
                        throw new \RuntimeException('WTF');
                    }

                    return new CountableProviderRecords(
                        (function () use ($records): \Generator {
                            yield [
                                'positive_reviews' => $records->getTotalPositive(),
                                'negative_reviews' => $records->getTotalNegative(),
                                'total_reviews' => count($records),
                            ];
                        })(),
                        count($records),
                        $records->getResource()
                    );
                }
            }
        );
    }
}
