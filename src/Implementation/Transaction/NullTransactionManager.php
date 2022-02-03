<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\Transaction;

use MakinaCorpus\CoreBus\Implementation\Transaction\Error\TransactionAlreadyRunningError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class NullTransactionManager implements TransactionManager, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?Transaction $currentTransaction = null;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function start(): Transaction
    {
        if ($this->currentTransaction && $this->currentTransaction->running()) {
            throw new TransactionAlreadyRunningError();
        }

        $this->logger->debug("NullTransactionManager: Transaction start.");

        $transaction = new NullTransaction();
        $transaction->setLogger($this->logger);

        return $this->currentTransaction = $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function running(): bool
    {
        return $this->currentTransaction && $this->currentTransaction->running();
    }
}
