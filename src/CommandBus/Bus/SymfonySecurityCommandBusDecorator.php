<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Bus;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\Message\Property;
use Symfony\Component\Security\Core\Security;

/**
 * Injects the user identifier into command properties.
 */
final class SymfonySecurityCommandBusDecorator extends AbstractCommandBus implements SynchronousCommandBus
{
    private Security $security;
    private CommandBus $decorated;

    public function __construct(Security $security, CommandBus $decorated)
    {
        $this->decorated = $decorated;
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command, ?array $properties = null): CommandResponsePromise
    {
        if ($user = $this->security->getUser()) {
            $properties[Property::USER_ID] = $user->getUserIdentifier();
        } else if ($token = $this->security->getToken()) {
            $properties[Property::USER_ID] = $token->getUserIdentifier();
        }

        return $this->decorated->dispatchCommand($command, $properties);
    }
}
