<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\EventBus;

use MakinaCorpus\CoreBus\EventBus\Bus\ContainerEventListenerLocator;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventA;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventB;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventC;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventInterfaceListener;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventListener;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventParentClassListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ContainerEventListenerLocatorTest extends TestCase
{
    public function testFind(): void
    {
        $serviceLocator = new ServiceLocator([
            'mock_listener' => fn () => new MockEventListener(),
        ]);
        $locator = new ContainerEventListenerLocator([
            'mock_listener' => MockEventListener::class,
        ], $serviceLocator);

        $eventA = new MockEventA();
        $iterable = $locator->find($eventA);
        self::assertSame(0, $eventA->count);
        foreach ($iterable as $callback) {
            $callback($eventA);
        }
        self::assertSame(3, $eventA->count);

        $eventB = new MockEventB();
        $iterable = $locator->find($eventB);
        self::assertSame(0, $eventB->count);
        foreach ($iterable as $callback) {
            $callback($eventB);
        }
        self::assertSame(1, $eventB->count);

        $eventC = new MockEventC();

        $iterable = $locator->find($eventC);
        self::assertSame(0, $eventC->count);
        foreach ($iterable as $callback) {
            $callback($eventC);
        }
        self::assertSame(0, $eventC->count);
    }

    public function testFindWithInterface(): void
    {
        $serviceLocator = new ServiceLocator([
            'mock_listener' => fn () => new MockEventInterfaceListener(),
        ]);
        $locator = new ContainerEventListenerLocator([
            'mock_listener' => MockEventInterfaceListener::class,
        ], $serviceLocator);

        $eventA = new MockEventA();
        $iterable = $locator->find($eventA);
        self::assertSame(0, $eventA->count);
        foreach ($iterable as $callback) {
            $callback($eventA);
        }
        self::assertSame(1, $eventA->count);

        $eventB = new MockEventB();
        $iterable = $locator->find($eventB);
        self::assertSame(0, $eventB->count);
        foreach ($iterable as $callback) {
            $callback($eventB);
        }
        self::assertSame(0, $eventB->count);

        $eventC = new MockEventC();

        $iterable = $locator->find($eventC);
        self::assertSame(0, $eventC->count);
        foreach ($iterable as $callback) {
            $callback($eventC);
        }
        self::assertSame(1, $eventC->count);
    }

    public function testFindWithParentClass(): void
    {
        $serviceLocator = new ServiceLocator([
            'mock_listener' => fn () => new MockEventParentClassListener(),
        ]);
        $locator = new ContainerEventListenerLocator([
            'mock_listener' => MockEventParentClassListener::class,
        ], $serviceLocator);

        $eventA = new MockEventA();
        $iterable = $locator->find($eventA);
        self::assertSame(0, $eventA->count);
        foreach ($iterable as $callback) {
            $callback($eventA);
        }
        self::assertSame(1, $eventA->count);

        $eventB = new MockEventB();
        $iterable = $locator->find($eventB);
        self::assertSame(0, $eventB->count);
        foreach ($iterable as $callback) {
            $callback($eventB);
        }
        self::assertSame(1, $eventB->count);

        $eventC = new MockEventC();

        $iterable = $locator->find($eventC);
        self::assertSame(0, $eventC->count);
        foreach ($iterable as $callback) {
            $callback($eventC);
        }
        self::assertSame(0, $eventC->count);
    }
}
