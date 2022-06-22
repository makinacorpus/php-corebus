<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Bridge\EventStore;

use MakinaCorpus\CoreBus\Bridge\EventStore\EventInfoExtratorChain;
use MakinaCorpus\CoreBus\Bridge\EventStore\EventStoreCommandBusDecorator;
use MakinaCorpus\CoreBus\Implementation\CommandBus\NullCommandBus;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandA;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandAsEvent;
use MakinaCorpus\EventStore\Testing\DummyArrayEventStore;
use PHPUnit\Framework\TestCase;

final class EventStoreCommandBusDecoratorTest extends TestCase
{
    public function testCommandAsEventIsIgnored(): void
    {
        $eventStore = new DummyArrayEventStore();
        $commandBus = new NullCommandBus();
        $eventInfoExtractor = new EventInfoExtratorChain([]);

        $decorator = new EventStoreCommandBusDecorator($commandBus, $eventStore, $eventInfoExtractor);

        $command = new MockCommandA();

        self::assertSame(0, $eventStore->countStored());
        $decorator->dispatchCommand($command);
        self::assertSame(1, $eventStore->countStored());
        self::assertSame($command, $eventStore->getStored()[0]->getMessage());

        $decorator->dispatchCommand(new MockCommandAsEvent());
        self::assertSame(1, $eventStore->countStored());
    }
}
