<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\EventBus;

use MakinaCorpus\CoreBus\EventBus\Bus\NullEventBus;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventA;
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
