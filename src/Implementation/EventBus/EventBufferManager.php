<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\EventBus;

interface EventBufferManager
{
    /**
     * Start new event buffer.
     */
    public function start(): EventBuffer;
}
