<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Transaction;

use MakinaCorpus\CoreBus\Implementation\Transaction\NullTransactionManager;
use MakinaCorpus\CoreBus\Implementation\Transaction\Error\TransactionAlreadyRunningError;
use PHPUnit\Framework\TestCase;

final class NullTransactionManagerTest extends TestCase
{
    public function testStartWithAlreadyExistingClosedTransaction(): void
    {
        $transactionManager = new NullTransactionManager();

        self::assertFalse($transactionManager->running());

        $transaction = $transactionManager->start();
        $transaction->commit();

        $transactionManager->start();

        self::assertTrue($transactionManager->running());
    }

    public function testStartWithAlreadyExistingTransactionRaiseError(): void
    {
        $transactionManager = new NullTransactionManager();

        self::assertFalse($transactionManager->running());

        $transactionManager->start();

        self::expectException(TransactionAlreadyRunningError::class);

        $transactionManager->start();
    }
}
