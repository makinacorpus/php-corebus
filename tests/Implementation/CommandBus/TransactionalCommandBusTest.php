<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\CommandBus;

use MakinaCorpus\CoreBus\Attr\NoTransaction;
use MakinaCorpus\CoreBus\Attribute\AttributeList;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\DefaultAttributeList;
use MakinaCorpus\CoreBus\Attribute\Loader\NullAttributeLoader;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;
use MakinaCorpus\CoreBus\CommandBus\Transaction\MultiCommand;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\EventBus\EventBusAwareTrait;
use MakinaCorpus\CoreBus\Implementation\CommandBus\TransactionalCommandBus;
use MakinaCorpus\CoreBus\Implementation\CommandBus\Response\SynchronousCommandResponsePromise;
use MakinaCorpus\CoreBus\Implementation\EventBus\ArrayEventBufferManager;
use MakinaCorpus\CoreBus\Implementation\Transaction\Transaction;
use MakinaCorpus\CoreBus\Implementation\Transaction\TransactionManager;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandA;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandB;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandC;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventA;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventB;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventBus;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventC;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockResponse;
use PHPUnit\Framework\TestCase;

final class TransactionalCommandBusTest extends TestCase
{
    public function testCommitFlushEventBuffer(): void
    {
        $internalCommandBus = new TransactionalCommandBusTestCommandBus();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $commandBus = $this->createCommandBus(
            $internalCommandBus,
            $internalEventBus,
            $externalEventBus
        );
        $internalCommandBus->setEventBus($commandBus);

        $commandBus->dispatchCommand(new MockCommandA());

        self::assertCount(1, $externalEventBus->events);
        self::assertCount(1, $internalEventBus->events);

        $externalEventBus->events = [];
        $internalEventBus->events = [];

        $commandBus->dispatchCommand(new MockCommandC());

        self::assertCount(3, $externalEventBus->events);
        self::assertCount(3, $internalEventBus->events);
    }

    public function testRollbackDiscardEventBuffer(): void
    {
        $internalCommandBus = new TransactionalCommandBusTestCommandBus();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $commandBus = $this->createCommandBus(
            $internalCommandBus,
            $internalEventBus,
            $externalEventBus
        );
        $internalCommandBus->setEventBus($commandBus);

        try {
            $commandBus->dispatchCommand(new MockCommandB());
        } catch (\DomainException $e) {
        }

        self::assertCount(2, $internalEventBus->events);
        self::assertCount(0, $externalEventBus->events);
    }

    public function testTransactionDefaultEnabled(): void
    {
        $internalCommandBus = new TransactionalCommandBusTestCommandBus();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $attributeLoader = new NullAttributeLoader();
        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandBus(
            $internalCommandBus,
            $internalEventBus,
            $externalEventBus,
            $attributeLoader,
            $transactionManager
        );
        $internalCommandBus->setEventBus($commandBus);

        $commandBus->dispatchCommand(new MockCommandA());

        self::assertTrue($transactionManager->getCurrentTransaction()->isCommited());
    }

    public function testTransactionDisabledWithAttribute(): void
    {
        $internalCommandBus = new TransactionalCommandBusTestCommandBus();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $attributeLoader = new class () extends NullAttributeLoader
        {
            public function loadFromClass(string $className): AttributeList
            {
                return new DefaultAttributeList([
                    new NoTransaction(),
                ]);
            }
        };
        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandBus(
            $internalCommandBus,
            $internalEventBus,
            $externalEventBus,
            $attributeLoader,
            $transactionManager
        );
        $internalCommandBus->setEventBus($commandBus);

        $commandBus->dispatchCommand(new MockCommandA());

        self::expectExceptionMessage('No transaction is set or was done.');
        $transactionManager->getCurrentTransaction();
    }

    public function testMultiCommand(): void
    {
        $internalCommandBus = new TransactionalCommandBusTestCommandBus();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $attributeLoader = new NullAttributeLoader();
        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandBus(
            $internalCommandBus,
            $internalEventBus,
            $externalEventBus,
            $attributeLoader,
            $transactionManager
        );
        $internalCommandBus->setEventBus($commandBus);

        $commandBus->dispatchCommand(
            new MultiCommand([
                new MockCommandA(),
                new MockCommandA(),
                new MockCommandA(),
            ])
        );

        self::assertCount(3, $internalEventBus->events);
    }

    private function createCommandBus(
        CommandBus $commandBus,
        EventBus $internalEventBus,
        EventBus $externalEventBus,
        ?AttributeLoader $attributeLoader = null,
        ?TransactionManager $transactionManager = null
    ): TransactionalCommandBus{
        return new TransactionalCommandBus(
            $commandBus,
            $internalEventBus,
            $externalEventBus,
            new ArrayEventBufferManager(),
            $transactionManager ?? new TestingTransactionManager(),
            $attributeLoader ?? new NullAttributeLoader(),
        );
    }
}

/**
 * @internal
 */
final class TestingTransaction implements Transaction
{
    private string $status = 'running';

    /**
     * @return bool
     */
    public function running(): bool
    {
        return 'running' === $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $this->status = 'commit';
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(?\Throwable $previous = null): void
    {
        $this->status = 'rollback';
    }

    public function isCommited(): bool
    {
        return 'commit' === $this->status;
    }

    public function isRollbacked(): bool
    {
        return 'rollback' === $this->status;
    }
}

/**
 * @internal
 */
final class TestingTransactionManager implements TransactionManager
{
    private ?TestingTransaction $transaction = null;

    public function start(): Transaction
    {
        if ($this->running()) {
            throw new \Exception("Cannot nest transactions");
        }

        return $this->transaction = new TestingTransaction();
    }

    public function running(): bool
    {
        return $this->transaction && $this->transaction->running();
    }

    public function getCurrentTransaction(): TestingTransaction
    {
        if (!$this->transaction) {
            throw new \Exception("No transaction is set or was done.");
        }

        return $this->transaction;
    }
}

/**
 * @internal
 */
final class TransactionalCommandBusTestCommandBus implements CommandBus
{
    use EventBusAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        if ($command instanceof MockCommandA) {
            $this->getEventBus()->notifyEvent(new MockEventA());

            return SynchronousCommandResponsePromise::success(new MockResponse($command));
        }

        if ($command instanceof MockCommandB) {
            $this->getEventBus()->notifyEvent(new MockEventA());
            $this->getEventBus()->notifyEvent(new MockEventB());

            throw new \DomainException("This should rollback.");
        }

        if ($command instanceof MockCommandC) {
            $this->getEventBus()->notifyEvent(new MockEventA());
            $this->getEventBus()->notifyEvent(new MockEventB());
            $this->getEventBus()->notifyEvent(new MockEventC());

            return SynchronousCommandResponsePromise::success(new MockResponse($command));
        }

        throw CommandHandlerNotFoundError::fromCommand($command);
    }
}
