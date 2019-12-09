<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Patreon;

use ScriptFUSION\Mapper\AnonymousMapping;
use ScriptFUSION\Mapper\Strategy\Copy;
use ScriptFUSION\Porter\Collection\RecordCollection;
use ScriptFUSION\Porter\Provider\Patreon\Collection\PledgeRecords;
use ScriptFUSION\Porter\Provider\Patreon\Resource\GetPledges;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Porter\Transform\FilterTransformer;
use ScriptFUSION\Porter\Transform\Mapping\MappingTransformer;
use ScriptFUSION\Steam250\Import\Patreon\Strategy\RelationshipStrategy;

class PledgesSpecification extends ImportSpecification
{
    public function __construct()
    {
        parent::__construct(new GetPledges(1405455));

        $this->addTransformers([
            new class extends MappingTransformer {
                public function __construct()
                {
                    parent::__construct(new AnonymousMapping([
                        'about' => new Copy(
                            'attributes->about',
                            new RelationshipStrategy('patron', 'user')
                        ),
                        'reward' => new Copy(
                            'attributes->amount_cents',
                            new RelationshipStrategy('reward')
                        ),
                    ]));
                }

                public function transform(RecordCollection $records, $context): RecordCollection
                {
                    if (!$records instanceof PledgeRecords) {
                        throw new \InvalidArgumentException('Records must be of type: PledgeRecords.');
                    }

                    return parent::transform($records, $records->getLinkedResources());
                }
            },
            new FilterTransformer(static function (array $data): bool {
                return $data['reward'] >= 500;
            }),
        ]);
    }
}
