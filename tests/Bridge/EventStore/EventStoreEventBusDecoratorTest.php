<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Bridge\EventStore;

use MakinaCorpus\CoreBus\Bridge\EventStore\EventInfoExtratorChain;
use MakinaCorpus\CoreBus\Bridge\EventStore\EventStoreEventBusDecorator;
use MakinaCorpus\CoreBus\EventBus\Bus\NullEventBus;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandAsEvent;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventNoStore;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventStore;
use MakinaCorpus\EventStore\Event;
use MakinaCorpus\EventStore\Projector\Runtime\RuntimePlayer;
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

    public function testEventIsIgnoredWhenCommandAsEvent(): void
    {
        $eventStore = new DummyArrayEventStore();
        $eventBus = new NullEventBus();
        $eventInfoExtractor = new EventInfoExtratorChain([]);

        $decorator = new EventStoreEventBusDecorator($eventBus, $eventStore, $eventInfoExtractor);

        $command = new MockCommandAsEvent();

        self::assertSame(0, $eventStore->countStored());
        $decorator->notifyEvent($command);
        self::assertSame(0, $eventStore->countStored());
    }

    public function testEventIsProjected(): void
    {
        $eventStore = new DummyArrayEventStore();
        $eventBus = new NullEventBus();
        $eventInfoExtractor = new EventInfoExtratorChain([]);

        $runtimePlayer = new class () implements RuntimePlayer
        {
            public ?Event $dispatched = null;

            public function dispatch(Event $event): void
            {
                $this->dispatched = $event;
            }
        };

        $decorator = new EventStoreEventBusDecorator($eventBus, $eventStore, $eventInfoExtractor, $runtimePlayer);

        $event = new MockEventStore();

        self::assertNull($runtimePlayer->dispatched);
        $decorator->notifyEvent($event);
        self::assertSame($event, $runtimePlayer->dispatched->getMessage());
    }

    public function testEventIsNotProjectedWhenNoStore(): void
    {
        $eventStore = new DummyArrayEventStore();
        $eventBus = new NullEventBus();
        $eventInfoExtractor = new EventInfoExtratorChain([]);

        $runtimePlayer = new class () implements RuntimePlayer
        {
            public ?Event $dispatched = null;

            public function dispatch(Event $event): void
            {
                $this->dispatched = $event;
            }
        };

        $decorator = new EventStoreEventBusDecorator($eventBus, $eventStore, $eventInfoExtractor, $runtimePlayer);

        $event = new MockEventNoStore();

        self::assertNull($runtimePlayer->dispatched);
        $decorator->notifyEvent($event);
        self::assertNull($runtimePlayer->dispatched);
    }
}
