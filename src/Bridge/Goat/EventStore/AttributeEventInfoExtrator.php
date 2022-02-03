<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Goat\EventStore;

use MakinaCorpus\CoreBus\Attr\Aggregate;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;

final class AttributeEventInfoExtrator implements EventInfoExtrator
{
    /**
     * {@inheritdoc}
     */
    public function extract(object $event, EventInfo $eventInfo): void
    {
        $attribute = (new AttributeLoader())->loadFromClass($event)->first(Aggregate::class);

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
