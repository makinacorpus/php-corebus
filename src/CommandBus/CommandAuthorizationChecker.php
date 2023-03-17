<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * Provides a pluggable interface for checking command authorization.
 *
 * This API is unused in core features, it is meant to be plugged at command
 * input time, which is done using a command bus decorator.
 *
 * @see \MakinaCorpus\CoreBus\CommandBus\Bus\AuthorizationCommandBusDecorator
 */
interface CommandAuthorizationChecker
{
    /**
     * Check if command is allowed in the current context.
     *
     * Meaning of a context will depend upon of your application and framework.
     */
    public function isGranted(object $command): bool;
}
