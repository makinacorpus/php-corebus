<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\Transaction;

use MakinaCorpus\CoreBus\Implementation\Transaction\Error\TransactionAlreadyClosedError;

interface Transaction
{
    /**
     * Is transaction still running.
     */
    public function running(): bool;

    /**
     * Commit transaction, might raise any error.
     *
     * @throws TransactionAlreadyClosedError
     */
    public function commit(): void;

    /**
     * Rollback transaction, should not raise errors, but still can in case
     * something really, really wrong happened.
     *
     * @throws TransactionAlreadyClosedError
     */
    public function rollback(): void;
}
