<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * This interface allows automatic dependency injection via the container.
 */
interface CommandBusAware
{
    public function setCommandBus(CommandBus $commandBus): void;
}
