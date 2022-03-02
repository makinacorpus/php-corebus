<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

use MakinaCorpus\CoreBus\Attr\NoStore;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\EventStore\EventStore;
use MakinaCorpus\EventStore\Projector\Runtime\RuntimePlayer;
use MakinaCorpus\Message\Envelope;

/**
 * Stores command (and not events) into event store.
 *
 * This feature is for backward compatibility with some legacy projects which
 * actually use this event store as a command logging mechanism.
 *
 * This can only work on synchronous command bus.
 */
final class EventStoreCommandBusDecorator implements SynchronousCommandBus
{
    private SynchronousCommandBus $decorated;
    private EventStore $eventStore;
    private EventInfoExtrator $eventInfoExtractor;
    private ?RuntimePlayer $runtimePlayer = null;

    public function __construct(
        SynchronousCommandBus $decorated,
        EventStore $eventStore,
        EventInfoExtrator $eventInfoExtractor,
        ?RuntimePlayer $runtimePlayer = null
    ) {
        $this->decorated = $decorated;
        $this->eventInfoExtractor = $eventInfoExtractor;
        $this->eventStore = $eventStore;
        $this->runtimePlayer = $runtimePlayer;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $eventInfo = new EventInfo();
        $this->eventInfoExtractor->extract($command, $eventInfo);

        if ((new AttributeLoader())->loadFromClass($command)->has(NoStore::class)) {
            return $this->decorated->dispatchCommand($command);
        }

        if ($command instanceof Envelope) {
            $commandMessage = $command->getMessage();
            $commandProperties = $command->getProperties();
        } else {
            $commandMessage = $command;
            $commandProperties = [];
        }

        $storedEvent = $this
            ->eventStore
            ->append($commandMessage)
            ->aggregate(
                $eventInfo->getAggregateType(),
                $eventInfo->getAggregateId()
            )
            ->properties(
                $commandProperties
            )
            ->properties(
                $eventInfo->getProperties()
            )
            ->execute()
        ;

        try {
            $ret = $this->decorated->dispatchCommand($command);

            if ($this->runtimePlayer) {
                $this->runtimePlayer->dispatch($storedEvent);
            }

            return $ret;

        } catch (\Throwable $e) {
            $this
                ->eventStore
                ->failedWith($storedEvent, $e)
                ->execute()
            ;

            throw $e;
        }
    }
}
