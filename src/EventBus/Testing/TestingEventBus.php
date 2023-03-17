<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Testing;

use MakinaCorpus\CoreBus\EventBus\EventBus;
use Psr\Log\NullLogger;

/**
 * This class allows to replace your event bus during unit tests.
 *
 * @codeCoverageIgnore
 */
class TestingEventBus implements EventBus
{
    /** @var object[] */
    private array $events = [];
    /** @var object[] */
    private array $listeners = [];

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        $this->events[] = $event;
    }

    public function reset(): void
    {
        $this->events = [];
    }

    /** @return object[] */
    public function all(): array
    {
        return $this->events;
    }

    public function count(): int
    {
        return \count($this->events);
    }

    public function countWithClass(string $className): int
    {
        $count = 0;

        foreach ($this->events as $event) {
            if (\get_class($event) === $className) {
                ++$count;
            }
        }

        return $count;
    }

    public function countInstanceOf(string $className): int
    {
        $count = 0;

        foreach ($this->events as $event) {
            if ($event instanceof $className) {
                ++$count;
            }
        }

        return $count;
    }

    public function getAt(int $index)
    {
        if (!isset($this->events[$index])) {
            throw new \InvalidArgumentException(\sprintf("There is no event at index %d", $index));
        }

        return $this->events[$index];
    }

    public function first()
    {
        return $this->getAt(0);
    }

    public function firstWithClass(string $className)
    {
        foreach ($this->events as $event) {
            if (\get_class($event) === $className) {
                return $event;
            }
        }

        throw new \InvalidArgumentException(\sprintf("There is no event with class %s", $className));
    }

    public function firstInstanceOf(string $className)
    {
        foreach ($this->events as $event) {
            if ($event instanceof $className) {
                return $event;
            }
        }

        throw new \InvalidArgumentException(\sprintf("There is no event instance of %s", $className));
    }

    public function last()
    {
        return $this->getAt(\count($this->events) - 1);
    }
}
