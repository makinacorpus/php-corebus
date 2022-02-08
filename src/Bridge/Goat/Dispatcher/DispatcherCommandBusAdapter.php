<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Goat\Dispatcher;

use Goat\Dispatcher\Dispatcher;
use Goat\Dispatcher\MessageEnvelope;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;

/**
 * From the makinacorpus/goat dispatcher that fetches messages from message
 * broker, pass messages from the queue to our synchronous command bus.
 *
 * Because bundle ordering in your container may prevent this service from
 * overriding properly makinacorpus/goat one, this will be implemented as a
 * decorator of the service. The decorated service will never be reached.
 */
final class DispatcherCommandBusAdapter implements Dispatcher
{
    private Dispatcher $decorated;
    private CommandBus $commandBus;
    private SynchronousCommandBus $synchronousCommandBus;

    public function __construct(Dispatcher $decorated, CommandBus $commandBus, SynchronousCommandBus $synchronousCommandBus)
    {
        $this->decorated = $decorated;
        $this->commandBus = $commandBus;
        $this->synchronousCommandBus = $synchronousCommandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function process($message, array $properties = []): void
    {
        if ($message instanceof MessageEnvelope) {
            $message = $message->getMessage();
        }
        $this->synchronousCommandBus->dispatchCommand($message);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($message, array $properties = []): void
    {
        if ($message instanceof MessageEnvelope) {
            $message = $message->getMessage();
        }
        $this->commandBus->dispatchCommand($message);
    }
}
