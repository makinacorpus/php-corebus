<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\CommandBus\Worker;

use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Worker\Worker;
use MakinaCorpus\CoreBus\CommandBus\Worker\WorkerEvent;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\MessageBroker\MessageConsumer;
use PHPUnit\Framework\TestCase;

final class WorkerTest extends TestCase
{
    public function testIdleWillStop(): void
    {
        $commandConsumer = new class implements CommandConsumer
        {
            public function consumeCommand(object $command): CommandResponsePromise
            {
                throw new \DomainException("I am the expected error.");
            }
        };

        $messageConsumer = new class implements MessageConsumer
        {
            public function get(): ?Envelope
            {
                return null;
            }

            public function ack(Envelope $envelope): void
            {
                throw new \BadMethodCallException("I shall not be called.");
            }

            public function reject(Envelope $envelope, ?\Throwable $exception = null): void
            {
                throw new \BadMethodCallException("I shall not be called.");
            }
        };

        $worker = new Worker($commandConsumer, $messageConsumer);

        $hasIdled = false;
        $eventDispatcher = $worker->getEventDispatcher();

        $eventDispatcher
            ->addListener(
                WorkerEvent::IDLE,
                function () use ($worker, &$hasIdled) {
                    $worker->stop();
                    $hasIdled = true;
                }
            )
        ;

        self::assertFalse($hasIdled);

        $worker->run();

        self::assertTrue($hasIdled);
    }

    public function testIsResilientToError(): void
    {
        $commandConsumer = new class implements CommandConsumer
        {
            public function consumeCommand(object $command): CommandResponsePromise
            {
                throw new \DomainException("I am the expected error.");
            }
        };

        $messageConsumer = new class implements MessageConsumer
        {
            private int $rejectCallCount = 0;

            public function get(): ?Envelope
            {
                return Envelope::wrap(new \DateTime());
            }

            public function ack(Envelope $envelope): void
            {
                throw new \BadMethodCallException("I shall not be called.");
            }

            public function reject(Envelope $envelope, ?\Throwable $exception = null): void
            {
                $this->rejectCallCount++;
            }

            public function getRejectCallCount(): int
            {
                return $this->rejectCallCount;
            }
        };

        $worker = new Worker($commandConsumer, $messageConsumer);

        $caught = false;
        $eventDispatcher = $worker->getEventDispatcher();

        $eventDispatcher
            ->addListener(
                WorkerEvent::NEXT,
                function () use ($worker) {
                    $worker->stop();
                }
            )
        ;

        $eventDispatcher
            ->addListener(
                WorkerEvent::ERROR,
                function () use (&$caught, $worker) {
                    $caught = true;
                    $worker->stop();
                }
            )
        ;

        self::assertSame(0, $messageConsumer->getRejectCallCount());
        self::assertFalse($caught);

        $worker->run();

        self::assertSame(1, $messageConsumer->getRejectCallCount());
        self::assertTrue($caught);
    }
}
