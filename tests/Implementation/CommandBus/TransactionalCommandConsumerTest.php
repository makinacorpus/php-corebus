<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;
use MakinaCorpus\CoreBus\CommandBus\Transaction\MultiCommand;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\EventBus\EventBusAwareTrait;
use MakinaCorpus\CoreBus\Implementation\CommandBus\TransactionalCommandConsumer;
use MakinaCorpus\CoreBus\Implementation\CommandBus\Response\SynchronousCommandResponsePromise;
use MakinaCorpus\CoreBus\Implementation\EventBus\ArrayEventBufferManager;
use MakinaCorpus\CoreBus\Implementation\Transaction\Transaction;
use MakinaCorpus\CoreBus\Implementation\Transaction\TransactionManager;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandA;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandAsEvent;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandB;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandC;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandNoTransaction;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventA;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventB;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventBus;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockEventC;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockResponse;
use PHPUnit\Framework\TestCase;

final class TransactionalCommandConsumerTest extends TestCase
{
    public function testCommitFlushEventBuffer(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus
        );
        $decorated->setEventBus($commandBus);

        $commandBus->consumeCommand(new MockCommandA());

        self::assertCount(1, $externalEventBus->events);
        self::assertCount(1, $internalEventBus->events);

        $externalEventBus->events = [];
        $internalEventBus->events = [];

        $commandBus->consumeCommand(new MockCommandC());

        self::assertCount(3, $externalEventBus->events);
        self::assertCount(3, $internalEventBus->events);
    }

    public function testRollbackDiscardEventBuffer(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus
        );
        $decorated->setEventBus($commandBus);

        try {
            $commandBus->consumeCommand(new MockCommandB());
        } catch (\DomainException $e) {
        }

        self::assertCount(2, $internalEventBus->events);
        self::assertCount(0, $externalEventBus->events);
    }

    public function testTransactionDefaultEnabled(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus,
            $transactionManager
        );
        $decorated->setEventBus($commandBus);

        $commandBus->consumeCommand(new MockCommandA());

        self::assertTrue($transactionManager->getCurrentTransaction()->isCommited());
    }

    public function testTransactionDisabledWithAttribute(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus,
            $transactionManager
        );
        $decorated->setEventBus($commandBus);

        $commandBus->consumeCommand(new MockCommandNoTransaction());

        self::expectExceptionMessage('No transaction is set or was done.');
        $transactionManager->getCurrentTransaction();
    }

    public function testCommandAsEvent(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus,
            $transactionManager
        );
        $decorated->setEventBus($commandBus);

        $command = new MockCommandAsEvent();
        $commandBus->consumeCommand($command);

        self::assertContains($command, $externalEventBus->events);
        self::assertContains($command, $internalEventBus->events);
    }

    public function testCommandAsEventNot(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus,
            $transactionManager
        );
        $decorated->setEventBus($commandBus);

        $command = new MockCommandA();
        $commandBus->consumeCommand($command);

        self::assertNotContains($command, $externalEventBus->events);
        self::assertNotContains($command, $internalEventBus->events);
    }

    public function testMultiCommand(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus();
        $internalEventBus = new MockEventBus();

        $transactionManager = new TestingTransactionManager();

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus,
            $transactionManager
        );
        $decorated->setEventBus($commandBus);

        $commandBus->consumeCommand(
            new MultiCommand([
                new MockCommandA(),
                new MockCommandA(),
                new MockCommandA(),
            ])
        );

        self::assertCount(3, $internalEventBus->events);
    }

    private function createCommandConsumer(
        CommandConsumer $decorated,
        EventBus $internalEventBus,
        EventBus $externalEventBus,
        ?TransactionManager $transactionManager = null
    ): TransactionalCommandConsumer {
        return new TransactionalCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus,
            new ArrayEventBufferManager(),
            $transactionManager ?? new TestingTransactionManager()
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
final class TestingTransactionalCommandConsumer implements CommandConsumer
{
    use EventBusAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function consumeCommand(object $command): CommandResponsePromise
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

        if ($command instanceof MockCommandAsEvent) {
            return SynchronousCommandResponsePromise::success(new MockResponse($command));
        }

        if ($command instanceof MockCommandNoTransaction) {
            return SynchronousCommandResponsePromise::success(null);
        }

        throw CommandHandlerNotFoundError::fromCommand($command);
    }
}
