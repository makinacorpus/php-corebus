<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\GoatQuery;

use Goat\Runner\Transaction as RunnerTransaction;
use MakinaCorpus\CoreBus\Transaction\Transaction;
use MakinaCorpus\CoreBus\Transaction\Error\TransactionAlreadyClosedError;

final class GoatQueryTransaction implements Transaction
{
    private ?RunnerTransaction $transaction = null;

    public function __construct(RunnerTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function running(): bool
    {
        return $this->transaction && $this->transaction->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        if (!$this->transaction || !$this->transaction->isStarted()) {
            $this->transaction = null;

            throw new TransactionAlreadyClosedError();
        }

        $this->transaction->commit();
        $this->transaction = null;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        if (!$this->transaction || !$this->transaction->isStarted()) {
            $this->transaction = null;

            throw new TransactionAlreadyClosedError();
        }

        $this->transaction->rollback();
        $this->transaction = null;
    }
}
