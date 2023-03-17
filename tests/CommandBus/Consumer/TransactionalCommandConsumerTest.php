<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\CommandBus\Consumer;

use MakinaCorpus\CoreBus\Bridge\Testing\MockEventBus;
use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Consumer\TransactionalCommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;
use MakinaCorpus\CoreBus\CommandBus\Response\SynchronousCommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Transaction\MultiCommand;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\EventBus\EventBusAwareTrait;
use MakinaCorpus\CoreBus\EventBus\Buffer\ArrayEventBufferManager;
use MakinaCorpus\CoreBus\EventBus\Bus\NullEventBus;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandA;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandAsEvent;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandB;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandC;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandNoTransaction;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventA;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventB;
use MakinaCorpus\CoreBus\Tests\Mock\MockEventC;
use MakinaCorpus\CoreBus\Tests\Mock\MockResponse;
use MakinaCorpus\CoreBus\Transaction\Transaction;
use MakinaCorpus\CoreBus\Transaction\TransactionManager;
use PHPUnit\Framework\TestCase;

final class TransactionalCommandConsumerTest extends TestCase
{
    public function testCommitFlushEventBuffer(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus(new NullEventBus());
        $internalEventBus = new MockEventBus(new NullEventBus());

        $commandBus = $this->createCommandConsumer(
            $decorated,
            $internalEventBus,
            $externalEventBus
        );
        $decorated->setEventBus($commandBus);

        $commandBus->consumeCommand(new MockCommandA());

        self::assertCount(1, $externalEventBus->getAllEvents());
        self::assertCount(1, $internalEventBus->getAllEvents());

        $externalEventBus->reset();
        $internalEventBus->reset();

        $commandBus->consumeCommand(new MockCommandC());

        self::assertCount(3, $externalEventBus->getAllEvents());
        self::assertCount(3, $internalEventBus->getAllEvents());
    }

    public function testRollbackDiscardEventBuffer(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus(new NullEventBus());
        $internalEventBus = new MockEventBus(new NullEventBus());

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

        self::assertCount(2, $internalEventBus->getAllEvents());
        self::assertCount(0, $externalEventBus->getAllEvents());
    }

    public function testTransactionDefaultEnabled(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus(new NullEventBus());
        $internalEventBus = new MockEventBus(new NullEventBus());

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
        $externalEventBus = new MockEventBus(new NullEventBus());
        $internalEventBus = new MockEventBus(new NullEventBus());

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
        $externalEventBus = new MockEventBus(new NullEventBus());
        $internalEventBus = new MockEventBus(new NullEventBus());

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

        self::assertContains($command, $externalEventBus->getAllEvents());
        self::assertContains($command, $internalEventBus->getAllEvents());
    }

    public function testCommandAsEventNot(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus(new NullEventBus());
        $internalEventBus = new MockEventBus(new NullEventBus());

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

        self::assertNotContains($command, $externalEventBus->getAllEvents());
        self::assertNotContains($command, $internalEventBus->getAllEvents());
    }

    public function testMultiCommand(): void
    {
        $decorated = new TestingTransactionalCommandConsumer();
        $externalEventBus = new MockEventBus(new NullEventBus());
        $internalEventBus = new MockEventBus(new NullEventBus());

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

        self::assertCount(3, $internalEventBus->getAllEvents());
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
