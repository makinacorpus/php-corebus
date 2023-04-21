<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

use MakinaCorpus\CoreBus\Attr\CommandAsEvent;
use MakinaCorpus\CoreBus\Attr\NoStore;
use MakinaCorpus\CoreBus\Attr\Loader\AttributeLoader;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\EventStore\EventStore;
use MakinaCorpus\EventStore\Projector\Runtime\RuntimePlayer;

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
 *
 * @deprecated
 * @codeCoverageIgnore
 */
final class EventStoreEventBusDecorator implements EventBus
{
    private AttributeLoader $attributeLoader;
    private EventBus $decorated;
    private EventStore $eventStore;
    private EventInfoExtrator $eventInfoExtractor;
    private ?RuntimePlayer $runtimePlayer = null;

    public function __construct(
        EventBus $decorated,
        EventStore $eventStore,
        EventInfoExtrator $eventInfoExtractor,
        ?RuntimePlayer $runtimePlayer = null
    ) {
        $this->attributeLoader = new AttributeLoader();
        $this->decorated = $decorated;
        $this->eventInfoExtractor = $eventInfoExtractor;
        $this->eventStore = $eventStore;
        $this->runtimePlayer = $runtimePlayer;
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        if ($this->attributeLoader->classHas($event, [NoStore::class, CommandAsEvent::class])) {
            $this->decorated->notifyEvent($event);

            return;
        }

        $eventInfo = new EventInfo();
        $this->eventInfoExtractor->extract($event, $eventInfo);

        $storedEvent = $this
            ->eventStore
            ->append($event)
            ->aggregate(
                $eventInfo->getAggregateType(),
                $eventInfo->getAggregateId()
            )
            ->properties(
                $eventInfo->getProperties()
            )
            ->execute()
        ;

        $this->decorated->notifyEvent($event);

        if ($this->runtimePlayer) {
            $this->runtimePlayer->dispatch($storedEvent);
        }
    }
}
