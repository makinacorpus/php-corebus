<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Buffer;

final class ArrayEventBufferManager implements EventBufferManager
{
    public function start(): EventBuffer
    {
        return new ArrayEventBuffer();
    }
}
