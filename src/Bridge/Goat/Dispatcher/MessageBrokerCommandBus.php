<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Goat\Dispatcher;

use Goat\Dispatcher\MessageEnvelope;
use Goat\MessageBroker\MessageBroker;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\Implementation\CommandBus\Response\NeverCommandResponsePromise;

/**
 * From our command bus interface, catch messages and send them into
 * makinacorpus/goat message broker instead.
 */
final class MessageBrokerCommandBus implements CommandBus
{
    private MessageBroker $messageBroker;

    public function __construct(MessageBroker $messageBroker)
    {
        $this->messageBroker = $messageBroker;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $this->messageBroker->dispatch(MessageEnvelope::wrap($command));

        return new NeverCommandResponsePromise();
    }
}
