<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\RetryStrategy;

use Goat\Driver\Error\TransactionError;
use MakinaCorpus\CoreBus\Error\DispatcherRetryableError;
use MakinaCorpus\Message\Envelope;

final class DefaultRetryStrategy implements RetryStrategy
{
    private bool $retryWithoutRequeueOnDatabaseFailure = true;
    private int $retryCount = 3;

    public function __construct(
        bool $retryWithoutRequeueOnDatabaseFailure = true,
        int $retryCount = 3
    ) {
        $this->retryWithoutRequeueOnDatabaseFailure = $retryWithoutRequeueOnDatabaseFailure;
        $this->retryCount = $retryCount;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldRetry(Envelope $envelope, \Throwable $error): RetryStrategyResponse
    {
        if ($error instanceof TransactionError) {
            if ($this->retryWithoutRequeueOnDatabaseFailure) {
                return RetryStrategyResponse::retryWithoutRequeue("Transaction serialization failure")->withMaxCount($this->retryCount);
            }

            return RetryStrategyResponse::retry("Transaction serialization failure")->withMaxCount($this->retryCount);
        }

        if ($error instanceof DispatcherRetryableError) {
            return RetryStrategyResponse::retry("Dispatcher specialized error")->withMaxCount($this->retryCount);
        }

        /*
         * @todo
         *   Restore this feature using attributes.
         *
        if ($envelope->getMessage() instanceof RetryableMessage) {
            return RetryStrategyResponse::retry("Message specialized as retryable");
        }
         */

        return RetryStrategyResponse::reject();
    }
}