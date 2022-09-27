<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Testing;

use MakinaCorpus\CoreBus\EventBus\EventBus;

class MockEventBus implements EventBus
{
    /** @var mixed[] */
    private array $notified = [];
    private EventBus $decorated;

    public function __construct(EventBus $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * Reset internal state.
     */
    public function reset()
    {
        $this->notified = [];
    }

    /**
     * Get all events.
     */
    public function getAllEvents(): array
    {
        return $this->notified;
    }

    /**
     * Count events that were dispatched matching the given class
     */
    public function countNotified(string $eventClass)
    {
        $count = 0;
        foreach ($this->notified as $event) {
            if (\get_class($event) === $eventClass) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        $this->notified[] = $event;

        $this->decorated->notifyEvent($event);
    }
}
