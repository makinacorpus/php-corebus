<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\EventBus;

use MakinaCorpus\CoreBus\Implementation\EventBus\ArrayEventBufferManager;
use MakinaCorpus\CoreBus\Implementation\EventBus\EventBufferManager;

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
