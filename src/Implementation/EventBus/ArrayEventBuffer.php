<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\EventBus;

use MakinaCorpus\CoreBus\Implementation\EventBus\Error\EventBufferAlreadyClosedError;

final class ArrayEventBuffer implements EventBuffer
{
    private bool $closed = false;
    /** @var object[] */
    private array $buffer = [];

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function add(object $event): void
    {
        if ($this->closed) {
            throw new EventBufferAlreadyClosedError("Event buffer has already been flushed or discarded.");
        }

        $this->buffer[] = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): iterable
    {
        $this->closed = true;
        $events = $this->buffer;
        $this->buffer = [];

        // Self-calling closure ensures the generator has been started,
        // otherwise the whole object method would be the generator, and
        // $this->closed = true wouldn't be called until iterated, causing
        // possible self::add() to be called prior iterating.
        return (static fn () => yield from $events)();
    }

    /**
     * {@inheritdoc}
     */
    public function discard(): void
    {
        $this->closed = true;
        $this->buffer = [];
    }
}
