<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus;

/**
 * @codeCoverageIgnore
 */
trait EventBusAwareTrait /* implements EventBusAware */
{
    private ?EventBus $eventBus = null;

    /**
     * {@inheritdoc}
     */
    public function setEventBus(EventBus $eventBus): void
    {
        $this->eventBus = $eventBus;
    }

    /**
     * Get event bus.
     */
    protected function getEventBus(): EventBus
    {
        if (!$this->eventBus) {
            throw new \LogicException("Event bus was not set.");
        }

        return $this->eventBus;
    }
}
