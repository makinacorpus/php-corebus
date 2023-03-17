<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Mock;

use MakinaCorpus\CoreBus\EventBus\EventListenerLocator;

final class MockEventListenerLocator implements EventListenerLocator
{
    /** @var array<string, callable[]> */
    private array $listeners;

    /** @param array<string, callable[]> */
    public function __construct(array $listeners)
    {
        $this->listeners = $listeners;
    }

    /**
     * {@inheritdoc}
     */
    public function find(object $event): iterable
    {
        return $this->listeners[\get_class($event)] ?? [];
    }
}
