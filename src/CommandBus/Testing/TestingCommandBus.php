<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Testing;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Bus\AbstractCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Response\NeverCommandResponsePromise;

/**
 * This class allows to replace your command bus during unit tests.
 *
 * @codeCoverageIgnore
 */
class TestingCommandBus extends AbstractCommandBus implements CommandBus
{
    /** @var object[] */
    private array $commands = [];

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command, ?array $properties = null): CommandResponsePromise
    {
        $this->commands[] = $command;

        return new NeverCommandResponsePromise($properties);
    }

    public function reset(): void
    {
        $this->commands = [];
    }

    /** @return object[] */
    public function all(): array
    {
        return $this->commands;
    }

    public function count(): int
    {
        return \count($this->commands);
    }

    public function countWithClass(string $className): int
    {
        $count = 0;

        foreach ($this->commands as $command) {
            if (\get_class($command) === $className) {
                ++$count;
            }
        }

        return $count;
    }

    public function countInstanceOf(string $className): int
    {
        $count = 0;

        foreach ($this->commands as $command) {
            if ($command instanceof $className) {
                ++$count;
            }
        }

        return $count;
    }

    public function getAt(int $index)
    {
        if (!isset($this->commands[$index])) {
            throw new \InvalidArgumentException(\sprintf("There is no command at index %d", $index));
        }

        return $this->commands[$index];
    }

    public function first()
    {
        return $this->getAt(0);
    }

    public function firstWithClass(string $className)
    {
        foreach ($this->commands as $command) {
            if (\get_class($command) === $className) {
                return $command;
            }
        }

        throw new \InvalidArgumentException(\sprintf("There is no command with class %s", $className));
    }

    public function firstInstanceOf(string $className)
    {
        foreach ($this->commands as $command) {
            if ($command instanceof $className) {
                return $command;
            }
        }

        throw new \InvalidArgumentException(\sprintf("There is no command instance of %s", $className));
    }

    public function last()
    {
        return $this->getAt(\count($this->commands) - 1);
    }
}
