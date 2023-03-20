<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Testing;

use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Bus\AbstractCommandBus;

class MockSynchronousCommandBus extends AbstractCommandBus implements SynchronousCommandBus
{
    /** @var mixed[] */
    private $dispatched = [];
    private SynchronousCommandBus $decorated;
    private ?MockCommandBus $asyncBus = null;
    private bool $raiseErrorInAsyncDispatch = false;

    public function __construct(SynchronousCommandBus $decorated, bool $raiseErrorInAsyncDispatch = false)
    {
        $this->decorated = $decorated;
        $this->raiseErrorInAsyncDispatch = $raiseErrorInAsyncDispatch;
    }

    public function setAsyncBus(MockCommandBus $asyncBus): void
    {
        $this->asyncBus = $asyncBus;
    }

    /**
     * Reset internal state.
     */
    public function reset()
    {
        $this->dispatched = [];
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
    public function dispatchCommand(object $command, ?array $properties = null): CommandResponsePromise
    {
        $this->dispatched[] = $command;

        $exception = null;

        try {
            try {
                return $this->decorated->dispatchCommand($command, $properties);
            } catch (\Throwable $e) {
                $exception = $e;
            }
        } finally {
            if ($exception) {
                throw $exception;
            }

            // Pendant les tests, les handlers vont pusher d'autres commandes
            // dans le bus. Cependant, à cause de son histoire, les tests de
            // ce projet s'attendent à ce que ces commandes soient réellement
            // exécutées en synchrone. Alors voilà, on les relance après que
            // commande parente ait terminée pour éviter des fausses assertions
            // dans nos tests existants.
            if ($this->asyncBus) {
                try {
                    foreach ($this->asyncBus->flush() as $command) {
                        // C'est important que ce soit $this qui dispatche de
                        // nouveau les commandes: comme ça les sous-commandes
                        // dispatchées par les sous-commandes seront elles
                        // aussi catchées et traitées.
                        $this->dispatchCommand($command);
                    }
                } catch (\Throwable $e) {
                    // Sub-commands can fail, it's OK.
                    if ($this->raiseErrorInAsyncDispatch) {
                        throw $e;
                    }
                }
            }
        }
    }
}
