<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus;

interface EventListenerLocator
{
    /**
     * Find events for command.
     *
     * @return callable[]
     *   It may yield no results.
     */
    public function find(object $event): iterable;
}
