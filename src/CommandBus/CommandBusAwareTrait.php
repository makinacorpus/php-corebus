<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * @codeCoverageIgnore
 */
trait CommandBusAwareTrait /* implements CommandBusAware */
{
    private ?CommandBus $commandBus = null;

    /**
     * {@inheritdoc}
     */
    public function setCommandBus(CommandBus $commandBus): void
    {
        $this->commandBus = $commandBus;
    }

    protected function getCommandBus(): CommandBus
    {
        if (!$this->commandBus) {
            throw new \LogicException("Command bus was not set.");
        }

        return $this->commandBus;
    }
}
