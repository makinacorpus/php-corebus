<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\Testing;

use MakinaCorpus\CoreBus\Bridge\Testing\MockCommandBus;
use MakinaCorpus\CoreBus\Bridge\Testing\MockEventBus;
use MakinaCorpus\CoreBus\Bridge\Testing\MockSynchronousCommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Bus\AbstractCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Response\NeverCommandResponsePromise;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * For usage with PHPUnit, this trait relies on PHPUnit TestCase and Symfony
 * KernelTestCase classes used in conjunction.
 *
 * @see \PHPUnit\Framework\TestCase
 * @see \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 */
abstract class AbstractWithCommandBusTest extends KernelTestCase
{
    private ?MockSynchronousCommandBus $mockSynchronousBus = null;
    private ?MockCommandBus $mockCommandBus = null;
    private ?MockEventBus $mockEventBus = null;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->mockCommandBus = null;
        $this->mockSynchronousBus = null;
        $this->mockEventBus = null;

        parent::tearDown();
    }

    /**
     * Lazy command bus creation.
     */
    private function initializeCommandBus(): void
    {
        if (!$this->mockCommandBus) {
            $commandbus = self::getContainer()->get(CommandBus::class);
            if (!$commandbus instanceof MockCommandBus) {
                throw new \Exception(\sprintf("Ce service doit décorer 'corebus.command.bus.asynchronous' en utilisant la classe '%s' dans 'config/packages/test/services.yaml'", MockCommandBus::class));
            }
            $this->mockCommandBus = $commandbus;
        }

        if (!$this->mockSynchronousBus) {
            $synchronousCommandbus = self::getContainer()->get(SynchronousCommandBus::class);
            if (!$synchronousCommandbus instanceof MockSynchronousCommandBus) {
                throw new \Exception(\sprintf("Ce service doit décorer 'corebus.command.bus.synchronous' en utilisant la classe '%s' dans 'config/packages/test/services.yaml'", MockSynchronousCommandBus::class));
            }
            $this->mockSynchronousBus = $synchronousCommandbus;
        }

        $this->mockSynchronousBus->setAsyncBus($this->mockCommandBus);

        if (!$this->mockEventBus) {
            $mockEventBus = self::getContainer()->get('corebus.event.bus.internal');
            if (!$mockEventBus instanceof MockEventBus) {
                throw new \Exception(\sprintf("Ce service doit décorer 'corebus.event.bus.internal' en utilisant la classe '%s' dans 'config/packages/test/services.yaml'", MockEventBus::class));
            }
            $this->mockEventBus = $mockEventBus;
        }
    }

    /**
     * Create a null bus instance.
     */
    protected static function createNullCommandBus(): CommandBus
    {
        return new class() extends AbstractCommandBus implements CommandBus
        {
            /**
             * {@inheritdoc}
             */
            public function dispatchCommand(object $command, ?array $properties = null): CommandResponsePromise
            {
                return new NeverCommandResponsePromise($properties);
            }
        };
    }

    /**
     * Get the command bus.
     */
    final protected function getCommandBus(): MockSynchronousCommandBus
    {
        $this->initializeCommandBus();

        $this->mockCommandBus->reset();
        $this->mockSynchronousBus->reset();
        $this->mockEventBus->reset();

        return $this->mockSynchronousBus;
    }

    /**
     * Get the command bus.
     */
    final protected function getAsynchronousCommandBus(): MockCommandBus
    {
        $this->initializeCommandBus();

        $this->mockCommandBus->reset();
        $this->mockSynchronousBus->reset();

        return $this->mockCommandBus;
    }

    /**
     * Assert a command has been processed.
     */
    final protected function assertCommandBusProcessed(string $class, int $expected = 1)
    {
        $this->initializeCommandBus();

        $real = $this->mockSynchronousBus->countDispatched($class);

        self::assertEquals($expected, $real, \sprintf(
            "Failed to assert that %d %s events where processed, %d were processed",
            $expected, $class, $real
        ));
    }

    /**
     * Assert an event has been notified.
     */
    final protected function assertEventNotified(string $class, int $expected = 1)
    {
        $this->initializeCommandBus();

        $real = $this->mockEventBus->countNotified($class);

        self::assertEquals($expected, $real, \sprintf(
            "Failed to assert that %d %s events where notified, %d were notified",
            $expected, $class, $real
        ));
    }

    /**
     * Assert a command has been dispatched.
     */
    final protected function assertCommandBusDispatched(string $class, int $expected = 1)
    {
        $this->initializeCommandBus();

        $real = $this->mockCommandBus->countDispatched($class);

        self::assertEquals($expected, $real, \sprintf(
            "Failed to assert that %d %s events where dispatched, %d were dispatched",
            $expected, $class, $real
        ));
    }
}
