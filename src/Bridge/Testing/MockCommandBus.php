<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Testing;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Response\NeverCommandResponsePromise;

class MockCommandBus implements SynchronousCommandBus
{
    /** @var mixed[] */
    private $dispatched = [];
    private $pendingExecution = [];
    private CommandBus $decorated;

    public function __construct(CommandBus $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * Reset internal state.
     */
    public function reset()
    {
        $this->dispatched = [];
    }

    /**
     * Fetch all stored pending events.
     */
    public function flush(): array
    {
        $ret = $this->pendingExecution;

        $this->pendingExecution = [];

        return $ret;
    }

    /**
     * Count events that were dispatched matching the given class
     */
    public function countDispatched(string $eventClass)
    {
        $count = 0;
        foreach ($this->dispatched as $event) {
            if (\get_class($event) === $eventClass) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $this->dispatched[] = $command;
        $this->pendingExecution[] = $command;

        return new NeverCommandResponsePromise();
    }
}
