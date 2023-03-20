<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\Error\CommandResponseError;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandResponseNotReadyError;
use MakinaCorpus\Message\PropertyBag;

/**
 * This should be promise, PHP doesn't have them, we have a custom
 * interface that mimics it.
 */
interface CommandResponsePromise
{
    /**
     * Get handler response.
     *
     * @throws CommandResponseError
     * @throws CommandResponseNotReadyError
     *
     * @return mixed
     *   Anything the handler returned.
     */
    public function get();

    /**
     * Get sent message properties.
     */
    public function getProperties(): PropertyBag;

    /**
     * Is response ready.
     */
    public function isReady(): bool;

    /**
     * If response ready, is it an error.
     */
    public function isError(): bool;
}
