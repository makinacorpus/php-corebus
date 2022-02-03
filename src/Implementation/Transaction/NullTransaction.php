<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\Transaction;

use MakinaCorpus\CoreBus\Implementation\Transaction\Error\TransactionAlreadyClosedError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class NullTransaction implements Transaction, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private bool $running = true;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function running(): bool
    {
        return $this->running;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        if (!$this->running) {
            throw new TransactionAlreadyClosedError("NullTransaction: Transaction was already closed.");
        }

        $this->logger->debug("NullTransaction: Transaction commit.");

        $this->running = false;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        if (!$this->running) {
            throw new TransactionAlreadyClosedError("NullTransaction: Transaction was already closed.");
        }

        $this->logger->debug("NullTransaction: Transaction rollback.");

        $this->running = false;
    }
}
