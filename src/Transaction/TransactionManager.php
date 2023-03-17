<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Transaction;

use MakinaCorpus\CoreBus\Transaction\Error\TransactionAlreadyRunningError;

interface TransactionManager
{
    /**
     * @throws TransactionAlreadyRunningError
     */
    public function start(): Transaction;

    /**
     * Is there a transaction running.
     */
    public function running(): bool;
}
