<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Response;

use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandResponseError;

/**
 * Promise for command bus that run synchronously.
 */
final class SynchronousCommandResponsePromise extends AbstractCommandResponsePromise
{
    private $response = null;
    private $error = null;
    private bool $isError = false;

    private function __construct($response, $error, bool $isError = false, ?array $properties = null)
    {
        parent::__construct($properties);

        $this->response = $response;
        $this->error = $error;
        $this->isError = $isError;
    }

    public static function success($response, ?array $properties = null): CommandResponsePromise
    {
        return new self($response, null, false, $properties);
    }

    public static function error($error, ?array $properties = null): CommandResponsePromise
    {
        return new self(null, $error, true, $properties);
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
