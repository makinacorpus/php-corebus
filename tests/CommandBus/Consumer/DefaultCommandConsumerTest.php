<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\CommandBus\Consumer;

use MakinaCorpus\CoreBus\CommandBus\Consumer\DefaultCommandConsumer;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandA;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandB;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandC;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandHandlerLocator;
use MakinaCorpus\CoreBus\Tests\Mock\MockResponse;
use PHPUnit\Framework\TestCase;

final class DefaultCommandConsumerTest extends TestCase
{
    public function testDispatchCommand(): void
    {
        $callCount = 0;

        $handler = static function ($command) use (&$callCount) {
            ++$callCount;

            return new MockResponse($command);
        };

        $handlerLocator = new MockCommandHandlerLocator([
            MockCommandA::class =>  $handler,
        ]);

        $commandConsumer = new DefaultCommandConsumer($handlerLocator);
        $command = new MockCommandA();

        self::assertSame(0, $callCount);

        $response = $commandConsumer->consumeCommand($command);

        self::assertSame(1, $callCount);
        self::assertTrue($response->isReady());
        self::assertFalse($response->isError());

        $realResponse = $response->get();

        self::assertInstanceOf(MockResponse::class, $realResponse);
        self::assertSame($command, $realResponse->command);
    }

    public function testDispatchCommandWithNullResponse(): void
    {
        $callCount = 0;

        $handler = static function ($command) use (&$callCount) {
            ++$callCount;

            return null;
        };

        $handlerLocator = new MockCommandHandlerLocator([
            MockCommandB::class =>  $handler,
        ]);

        $commandConsumer = new DefaultCommandConsumer($handlerLocator);
        $command = new MockCommandB();

        self::assertSame(0, $callCount);

        $response = $commandConsumer->consumeCommand($command);

        self::assertSame(1, $callCount);
        self::assertTrue($response->isReady());
        self::assertFalse($response->isError());
        self::assertNull($response->get());
    }

    public function testDispatchCommandAcceptAnythingResponse(): void
    {
        $handler = fn () => 1;
        $handlerLocator = new MockCommandHandlerLocator([
            MockCommandC::class =>  $handler,
        ]);

        $commandConsumer = new DefaultCommandConsumer($handlerLocator);
        $command = new MockCommandC();

        $response = $commandConsumer->consumeCommand($command);

        self::assertTrue($response->isReady());
        self::assertFalse($response->isError());
        self::assertSame(1, $response->get());
    }
}
