<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Bus;

use MakinaCorpus\CoreBus\CommandBus\CommandAuthorizationChecker;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\Error\UnauthorizedCommandError;

/**
 * Decorator implements SynchronousCommandBus so that it can be used for both
 * synchronous and asynchronous commands buses.
 */
final class AuthorizationCommandBusDecorator implements SynchronousCommandBus
{
    private CommandAuthorizationChecker $authorizationChecker;
    private CommandBus $decorated;

    public function __construct(CommandAuthorizationChecker $authorizationChecker, CommandBus $decorated)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        if (!$this->authorizationChecker->isGranted($command)) {
            throw new UnauthorizedCommandError();
        }

        return $this->decorated->dispatchCommand($command);
    }
}