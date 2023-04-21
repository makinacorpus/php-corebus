<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\CommandBus\Bus;

use MakinaCorpus\ArgumentResolver\DefaultArgumentResolver;
use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;
use MakinaCorpus\ArgumentResolver\Resolver\ArgumentValueResolver;
use MakinaCorpus\ArgumentResolver\Resolver\ContextArgumentValueResolver;
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

    public function testUseArgumentResolver(): void
    {
        $serviceLocator = new ServiceLocator([
            'do_something' => fn () => new DoSomethingHandler(),
        ]);

        $argumentResolver = new DefaultArgumentResolver(
            null,
            [
                new ContextArgumentValueResolver(),
                new DoSomethingArgumentValueResolver(),
            ]
        );

        $locator = new ContainerCommandHandlerLocator(
            [
                'do_something' => DoSomethingHandler::class,
            ],
            $serviceLocator,
            $argumentResolver
        );

        $command = new DoSomething();
        $callback = $locator->find($command);

        self::assertSame(0, $command->count);
        $callback($command);
        self::assertSame(1, $command->count);
    }
}

/**
 * @see ContainerCommandHandlerLocatorTest::testUseArgumentResolver()
 */
class DoSomething
{
    public int $count = 0;
}

/**
 * @see ContainerCommandHandlerLocatorTest::testUseArgumentResolver()
 */
class DoSomethingHandler
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: 'theCommand')]
    public function thisWillRun(DoSomething $theCommand, \DateTime $otherParameter): void
    {
        $theCommand->count++;
    }
}

/**
 * @see ContainerCommandHandlerLocatorTest::testUseArgumentResolver()
 */
class DoSomethingArgumentValueResolver implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        return $argument->getName() === 'otherParameter';
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        yield new \DateTime();
    }
}
