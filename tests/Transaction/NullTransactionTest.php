<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Transaction;

use MakinaCorpus\CoreBus\Transaction\NullTransaction;
use MakinaCorpus\CoreBus\Transaction\Error\TransactionAlreadyClosedError;
use PHPUnit\Framework\TestCase;

final class NullTransactionTest extends TestCase
{
    public function testCommit(): void
    {
        $transaction = new NullTransaction();

        self::assertTrue($transaction->running());

        $transaction->commit();

        self::assertFalse($transaction->running());
    }

    public function testRollback(): void
    {
        $transaction = new NullTransaction();

        self::assertTrue($transaction->running());

        $transaction->rollback();

        self::assertFalse($transaction->running());
    }

    public function testCommitAfterRollbackRaiseError(): void
    {
        $transaction = new NullTransaction();
        $transaction->rollback();

        self::expectException(TransactionAlreadyClosedError::class);

        $transaction->commit();
    }

    public function testRollbackAfterCommitRaiseError(): void
    {
        $transaction = new NullTransaction();
        $transaction->commit();

        self::expectException(TransactionAlreadyClosedError::class);

        $transaction->rollback();
    }
}
