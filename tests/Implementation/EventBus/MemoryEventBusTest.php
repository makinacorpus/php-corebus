<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\EventBus;

use MakinaCorpus\CoreBus\Implementation\EventBus\MemoryEventBus;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventA;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventB;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventListenerLocator;
use PHPUnit\Framework\TestCase;

final class MemoryEventBusTest extends TestCase
{
    public function testNotifyEvent(): void
    {
        $callCount = 0;

        $listener = static function (object $event) use (&$callCount) {
            ++$callCount;
        };

        $eventListenerLocator = new MockEventListenerLocator([
            MockEventA::class => [
                $listener,
                $listener,
                $listener,
            ],
            MockEventB::class => [],
        ]);

        $eventBus = new MemoryEventBus($eventListenerLocator);

        self::assertSame(0, $callCount);

        $eventBus->notifyEvent(new MockEventA());

        self::assertSame(3, $callCount);
    }

    public function testNotifyEventWhenNoListener(): void
    {
        $callCount = 0;

        $listener = static function (object $event) use (&$callCount) {
            ++$callCount;
        };

        $eventListenerLocator = new MockEventListenerLocator([
            MockEventA::class => [
                $listener,
                $listener,
                $listener,
            ],
            MockEventB::class => [],
        ]);

        $eventBus = new MemoryEventBus($eventListenerLocator);

        self::assertSame(0, $callCount);

        $eventBus->notifyEvent(new MockEventB());

        self::assertSame(0, $callCount);
    }
}
