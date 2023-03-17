<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\EventBus;

use MakinaCorpus\CoreBus\EventBus\Buffer\EventBufferManager;
use MakinaCorpus\CoreBus\EventBus\Error\EventBufferAlreadyClosedError;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventA;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventB;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventC;
use PHPUnit\Framework\TestCase;

abstract class AbstractEventBufferTest extends TestCase
{
    protected abstract function createEventBufferManager(): EventBufferManager;

    public function testRuntime(): void
    {
        $manager = $this->createEventBufferManager();

        $event1 = new MockEventA();
        $event2 = new MockEventB();
        $event3 = new MockEventC();

        $buffer = $manager->start();
        $buffer->add($event1);
        $buffer->add($event2);
        $buffer->add($event3);

        self::assertSame(3, $buffer->count());

        self::assertSame(
            [
                $event1,
                $event2,
                $event3,
            ],
            \iterator_to_array(
                $buffer->flush()
            )
        );
    }

    public function testAddRaiseErrorWhenFlushed(): void
    {
        $manager = $this->createEventBufferManager();

        $buffer = $manager->start();
        $buffer->flush();

        self::expectException(EventBufferAlreadyClosedError::class);
        $buffer->add(new MockEventA());
    }

    public function testAddRaiseErrorWhenDiscarded(): void
    {
        $manager = $this->createEventBufferManager();

        $buffer = $manager->start();
        $buffer->discard();

        self::expectException(EventBufferAlreadyClosedError::class);
        $buffer->add(new MockEventA());
    }
}
