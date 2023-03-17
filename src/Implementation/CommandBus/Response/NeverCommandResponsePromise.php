<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus\Response;

use MakinaCorpus\CoreBus\CommandBus\Error\CommandResponseNotReadyError;

/**
 * Response for bus that cannot poll handler result.
 */
final class NeverCommandResponsePromise extends AbstractCommandResponsePromise
{
    /**
     * {@inheritdoc}
     */
    public function get()
    {
        throw new CommandResponseNotReadyError("This command bus cannot fetch handler result.");
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return false;
    }
}
