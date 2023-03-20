<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

use MakinaCorpus\CoreBus\Attr\Aggregate;
use MakinaCorpus\CoreBus\Attr\Loader\AttributeLoader;

final class AttributeEventInfoExtrator implements EventInfoExtrator
{
    private AttributeLoader $attributeLoader;

    public function __construct()
    {
        $this->attributeLoader = new AttributeLoader();
    }

    /**
     * {@inheritdoc}
     */
    public function extract(object $event, EventInfo $eventInfo): void
    {
        $attribute = $this->attributeLoader->loadFromClass($event)->first(Aggregate::class);

        if ($attribute) {
            \assert($attribute instanceof Aggregate);

            if ($aggregateType = $attribute->getAggregateType()) {
                $eventInfo->withAggregateType($aggregateType);
            }
            if ($aggregateId = UuidHelper::normalize($attribute->findAggregateId($event))) {
                $eventInfo->withAggregateId($aggregateId);
            }
        }
    }
}
