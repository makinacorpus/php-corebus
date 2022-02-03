<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Goat\EventStore;

use Goat\EventStore\EventStore;
use MakinaCorpus\CoreBus\Attr\NoStore;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\EventBus\EventBus;

/**
 * Stoque les événements qui passent par lui dans l'event store.
 *
 * Deux possibilité où le brancher:
 *
 *   - soit sur le "internal event bus", c'est à dire celui qui dispatche les
 *     domain events en mode synchrône, cas dans lequel le store se fera sur la
 *     même transaction que le code métier,
 *     Dans ce cas, on décore le service 'corebus.event.bus.internal'.
 *
 *   - soit sur le "external event bus", c'est à dire celui qui est déstiné à
 *     faire sortir les événements de l'hexagone à destination des applications
 *     externes, cas dans lequel le store ne sera PAS dans la transaction, on
 *     pourrait donc perdre de l'historique.
 *     Dans ce cas, on décore le service 'corebus.event.bus.external'.
 *
 * Dans une premier temps, on va le brancher sur le bus interne, cf. le fichier
 * config/services/bus.yaml, il va décorer 'corebus.event.bus.internal'.
 */
final class EventStoreEventBusDecorator implements EventBus
{
    private EventBus $decorated;
    private EventStore $eventStore;
    private EventInfoExtrator $eventInfoExtractor;
    private AttributeLoader $attributeLoader;

    public function __construct(
        EventBus $decorated,
        EventStore $eventStore,
        EventInfoExtrator $eventInfoExtractor,
        AttributeLoader $attributeLoader
    ) {
        $this->attributeLoader = $attributeLoader;
        $this->decorated = $decorated;
        $this->eventInfoExtractor = $eventInfoExtractor;
        $this->eventStore = $eventStore;
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        $eventInfo = new EventInfo();
        $this->eventInfoExtractor->extract($event, $eventInfo);

        if ($this->attributeLoader->loadFromClass(\get_class($event))->has(NoStore::class)) {
            return;
        }

        $this
            ->eventStore
            ->append($event)
            ->aggregate(
                $eventInfo->getAggregateType(),
                $eventInfo->getAggregateId()
            )
            ->properties($eventInfo->getProperties())
            ->execute()
        ;

        $this->decorated->notifyEvent($event);
    }
}
