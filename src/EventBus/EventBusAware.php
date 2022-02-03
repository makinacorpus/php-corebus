<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus;

/**
 * This interface allows automatic dependency injection via the container.
 */
interface EventBusAware
{
    public function setEventBus(EventBus $eventBus): void;
}
