<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Bus;

use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\AbstractContainerCallableLocator;
use MakinaCorpus\CoreBus\CommandBus\CommandHandlerLocator;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;

final class ContainerCommandHandlerLocator extends AbstractContainerCallableLocator implements CommandHandlerLocator
{
    /**
     * {@inheritdoc}
     */
    protected function allowMultiple(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function find($command): callable
    {
        $reference = $this->referenceList->first(\get_class($command));

        if (!$reference) {
            throw CommandHandlerNotFoundError::fromCommand($command);
        }

        return $this->createCallable($reference);
    }
}
