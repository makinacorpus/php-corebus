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
 */
final class DispatcherCommandBusAdapter implements Dispatcher
{
    private CommandBus $commandBus;
    private SynchronousCommandBus $synchronousCommandBus;

    public function __construct(CommandBus $commandBus, SynchronousCommandBus $synchronousCommandBus)
    {
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
