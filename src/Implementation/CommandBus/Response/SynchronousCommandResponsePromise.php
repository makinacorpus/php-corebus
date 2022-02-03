<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus\Response;

use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandResponseError;

/**
 * Promise for command bus that run synchronously.
 */
final class SynchronousCommandResponsePromise implements CommandResponsePromise
{
    private $response = null;
    private $error = null;
    private bool $isError = false;

    private function __construct($response, $error, bool $isError = false)
    {
        $this->response = $response;
        $this->error = $error;
        $this->isError = $isError;
    }

    public static function success($response): CommandResponsePromise
    {
        return new self($response, null, false);
    }

    public static function error($error): CommandResponsePromise
    {
        return new self(null, $error, true);
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if ($this->isError) {
            throw new CommandResponseError();
        }

        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return $this->isError;
    }
}
