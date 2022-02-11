<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\GoatQuery;

use Goat\Runner\Runner;
use MakinaCorpus\CoreBus\Implementation\Transaction\Transaction;
use MakinaCorpus\CoreBus\Implementation\Transaction\TransactionManager;
use MakinaCorpus\CoreBus\Implementation\Transaction\Error\TransactionAlreadyRunningError;

final class GoatQueryTransactionManager implements TransactionManager
{
    private Runner $runner;
    private ?GoatQueryTransaction $current = null;

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): Transaction
    {
        if ($this->current && $this->current->running()) {
            throw new TransactionAlreadyRunningError();
        }

        return $this->current = new GoatQueryTransaction($this->runner->beginTransaction()); // Default level is REPEATABLE READ.
    }

    /**
     * {@inheritdoc}
     */
    public function running(): bool
    {
        return $this->current && $this->current->running();
    }
}
