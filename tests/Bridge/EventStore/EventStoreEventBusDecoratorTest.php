<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Bridge\EventStore;

use MakinaCorpus\CoreBus\Bridge\EventStore\EventInfoExtratorChain;
use MakinaCorpus\CoreBus\Bridge\EventStore\EventStoreEventBusDecorator;
use MakinaCorpus\CoreBus\Implementation\EventBus\NullEventBus;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventNoStore;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventStore;
use MakinaCorpus\EventStore\Testing\DummyArrayEventStore;
use PHPUnit\Framework\TestCase;

final class EventStoreEventBusDecoratorTest extends TestCase
{
    public function testEventIsStored(): void
    {
        $eventStore = new DummyArrayEventStore();
        $eventBus = new NullEventBus();
        $eventInfoExtractor = new EventInfoExtratorChain([]);

        $decorator = new EventStoreEventBusDecorator($eventBus, $eventStore, $eventInfoExtractor);

        $event = new MockEventStore();

        self::assertSame(0, $eventStore->countStored());
        $decorator->notifyEvent($event);
        self::assertSame(1, $eventStore->countStored());
        self::assertSame($event, $eventStore->getStored()[0]->getMessage());
    }

    public function testEventIsIgnoredWhenNoStore(): void
    {
        $eventStore = new DummyArrayEventStore();
        $eventBus = new NullEventBus();
        $eventInfoExtractor = new EventInfoExtratorChain([]);

        $decorator = new EventStoreEventBusDecorator($eventBus, $eventStore, $eventInfoExtractor);

        $event = new MockEventNoStore();

        self::assertSame(0, $eventStore->countStored());
        $decorator->notifyEvent($event);
        self::assertSame(0, $eventStore->countStored());
    }
}
