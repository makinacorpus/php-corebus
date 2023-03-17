<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Buffer;

use MakinaCorpus\CoreBus\EventBus\Error\EventBufferAlreadyClosedError;

interface EventBuffer extends \Countable
{
    /**
     * Add domain event to buffer.
     *
     * @throws EventBufferAlreadyClosedError
     */
    public function add(object $event): void;

    /**
     * Flush current buffer.
     */
    public function flush(): iterable;

    /**
     * Discard current buffer.
     */
    public function discard(): void;
}
