<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\CommandBus\Bus;

use MakinaCorpus\CoreBus\CommandBus\Bus\ContainerCommandHandlerLocator;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandA;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandB;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandC;
use MakinaCorpus\CoreBus\Tests\Mock\MockHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ContainerCommandHandlerLocatorTest extends TestCase
{
    public function testFind(): void
    {
        $serviceLocator = new ServiceLocator([
            'mock_handler' => fn () => new MockHandler(),
        ]);
        $locator = new ContainerCommandHandlerLocator([
            'mock_handler' => MockHandler::class,
        ], $serviceLocator);


        $commandA = new MockCommandA();
        $callback = $locator->find($commandA);
        self::assertFalse($commandA->done);
        self::assertNotNull($callback);

        $callback($commandA);
        self::assertTrue($commandA->done);

        $commandB = new MockCommandB();
        $callback = $locator->find($commandB);
        self::assertFalse($commandB->done);
        self::assertNotNull($callback);

        $callback($commandB);
        self::assertTrue($commandB->done);

        self::expectException(CommandHandlerNotFoundError::class);
        $locator->find(new MockCommandC());
    }
}
