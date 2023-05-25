<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Error;

use MakinaCorpus\CoreBus\CommandBus\RetryStrategy\RetryStrategyResponse;

/**
 * Error is retryable.
 *
 * When running a message handler, exceptions will be introspected, if it is
 * related to a transaction isolation kind of error, it will be wrapped into
 * this exception.
 *
 * It might also be triggered if the message implements the RetryableMessage
 * interface.
 *
 * Our message bus transport, which is agnostic from this namespace, will
 * find out its own Retryable error interface and re-queue event.
 *
 * @deprecated
 *   This exception must disapear.
 */
class DispatcherRetryableError extends \RuntimeException implements CoreBusError
{
    private ?RetryStrategyResponse $response = null;

    /**
     * Create instance from retry strategy response.
     */
    public static function fromResponse(RetryStrategyResponse $response, \Throwable $e): self
    {
        if ($e instanceof self) {
            $ret = $e;
        } else {
            $ret = new self($e->getMessage(), $e->getCode(), $e);
        }

        $ret->response = $response;

        return $ret;
    }

    public function getRetryStrategyResponse(): RetryStrategyResponse
    {
        return $this->response ?? RetryStrategyResponse::retry();
    }
}
