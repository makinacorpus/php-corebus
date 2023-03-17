<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Bus;

use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\EventBus\EventListenerLocator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class MemoryEventBus implements EventBus, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private EventListenerLocator $eventListenerLocator;

    public function __construct(EventListenerLocator $eventListenerLocator)
    {
        $this->eventListenerLocator = $eventListenerLocator;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        $this->logger->debug("MemoryEventBus: Received event: {event}", ['event' => $event]);

        $count = 0;

        foreach ($this->eventListenerLocator->find($event) as $eventListener) {
            ++$count;
            $eventListener($event);
        }

        $this->logger->debug("MemoryEventBus: Event dispatched to {count} listeners", ['count' => $count]);
    }
}
