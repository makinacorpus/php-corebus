<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

interface CommandHandlerLocator
{
    /**
     * Find handler for command.
     *
     * @throws \MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError
     */
    public function find($command): callable;
}
