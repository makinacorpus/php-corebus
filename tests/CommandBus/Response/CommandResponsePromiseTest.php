<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\CommandBus\Response;

use MakinaCorpus\CoreBus\CommandBus\Error\CommandResponseError;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandResponseNotReadyError;
use MakinaCorpus\CoreBus\CommandBus\Response\NeverCommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Response\SynchronousCommandResponsePromise;
use PHPUnit\Framework\TestCase;

final class CommandResponsePromiseTest extends TestCase
{
    public function testSynchronousError(): void
    {
        $response = SynchronousCommandResponsePromise::error('Foo');

        self::assertTrue($response->isReady());
        self::assertTrue($response->isError());

        self::expectException(CommandResponseError::class);
        $response->get();
    }

    public function testSynchronousSuccess(): void
    {
        $response = SynchronousCommandResponsePromise::success('Bar');

        self::assertTrue($response->isReady());
        self::assertFalse($response->isError());
        self::assertSame('Bar', $response->get());
    }

    public function testNever(): void
    {
        $response = new NeverCommandResponsePromise();

        self::assertFalse($response->isReady());
        self::assertFalse($response->isError());

        self::expectException(CommandResponseNotReadyError::class);
        $response->get();
    }
}
