<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\EventBus;

use MakinaCorpus\CoreBus\Implementation\EventBus\NullEventBus;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventA;
use PHPUnit\Framework\TestCase;

final class NullEventBusTest extends TestCase
{
    public function testNotifyEventDoesNothing(): void
    {
        $eventBus = new NullEventBus();
        $eventBus->notifyEvent(new MockEventA());

        self::assertNull(null);
    }
}
