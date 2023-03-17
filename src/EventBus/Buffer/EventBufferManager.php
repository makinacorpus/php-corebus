<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Buffer;

interface EventBufferManager
{
    /**
     * Start new event buffer.
     */
    public function start(): EventBuffer;
}
