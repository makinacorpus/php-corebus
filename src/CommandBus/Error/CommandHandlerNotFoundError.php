<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Error;

/**
 * @codeCoverageIgnore
 */
class CommandHandlerNotFoundError extends \DomainException
{
    public static function fromCommand($command): self
    {
        return new self(\sprintf("Handler for command %s could not be found.", \get_class($command)));
    }
}
