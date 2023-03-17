<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\EventBus;

use MakinaCorpus\CoreBus\EventBus\Buffer\ArrayEventBufferManager;
use MakinaCorpus\CoreBus\EventBus\Buffer\EventBufferManager;

final class ArrayEventBufferTest extends AbstractEventBufferTest
{
    /**
     * {@inheritdoc}
     */
    protected function createEventBufferManager(): EventBufferManager
    {
        return new ArrayEventBufferManager();
    }
}
