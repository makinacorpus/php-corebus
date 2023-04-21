<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

use MakinaCorpus\Message\BackwardCompat\AggregateMessage;

/**
 * Supports makinacorpus/goat interface.
 *
 * @deprecated
 * @codeCoverageIgnore
 */
final class LegacyAggregateEventInfoExtrator implements EventInfoExtrator
{
    /**
     * {@inheritdoc}
     */
    public function extract(object $event, EventInfo $eventInfo): void
    {
        if ($event instanceof AggregateMessage) {
            if ($aggregateType = $event->getAggregateType()) {
                $eventInfo->withAggregateType($aggregateType);
            }
            if ($aggregateId = $event->getAggregateId()) {
                $eventInfo->withAggregateId($aggregateId);
            }
            if ($aggregateRoot = $event->getAggregateRoot()) {
                $eventInfo->withAggregateRoot($aggregateRoot);
            }
        }
    }
}
