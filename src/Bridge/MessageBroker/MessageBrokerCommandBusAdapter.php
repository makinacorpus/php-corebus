<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\MessageBroker;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\Implementation\CommandBus\Response\NeverCommandResponsePromise;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\MessageBroker\MessageBroker;

/**
 * From our command bus interface, catch messages and send them into
 * makinacorpus/message-broker message broker instead.
 */
final class MessageBrokerCommandBusAdapter implements CommandBus
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
        $this->messageBroker->dispatch(Envelope::wrap($command));

        return new NeverCommandResponsePromise();
    }
}
