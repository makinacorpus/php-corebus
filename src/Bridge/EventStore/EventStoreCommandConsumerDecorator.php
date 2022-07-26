<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

use MakinaCorpus\CoreBus\Attr\NoStore;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
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
final class EventStoreCommandConsumerDecorator implements CommandConsumer
{
    private AttributeLoader $attributeLoader;
    private CommandConsumer $decorated;
    private EventStore $eventStore;
    private EventInfoExtrator $eventInfoExtractor;
    private ?RuntimePlayer $runtimePlayer = null;

    public function __construct(
        CommandConsumer $decorated,
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
    public function consumeCommand(object $command): CommandResponsePromise
    {
        if ($this->attributeLoader->classHas($command, NoStore::class)) {
            return $this->decorated->consumeCommand($command);
        }

        $eventInfo = new EventInfo();
        $this->eventInfoExtractor->extract($command, $eventInfo);

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
            $ret = $this->decorated->consumeCommand($command);

            // If a transaction went OK, but the command was marked to be
            // notified as being an domain event, we must not store it,
            // otherwise we will have a duplicate in the event stream.

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
