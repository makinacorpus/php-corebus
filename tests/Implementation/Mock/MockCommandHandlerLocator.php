<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Mock;

use MakinaCorpus\CoreBus\CommandBus\CommandHandlerLocator;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;

final class MockCommandHandlerLocator implements CommandHandlerLocator
{
    /** @var array<string, callable> */
    private array $handlers;

    /** @param array<string, callable> $handlers */
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function find($command): callable
    {
        return $this->handlers[\get_class($command)] ?? (
            static function () use ($command) {
                throw CommandHandlerNotFoundError::fromCommand($command);
            }
        )();
    }
}
