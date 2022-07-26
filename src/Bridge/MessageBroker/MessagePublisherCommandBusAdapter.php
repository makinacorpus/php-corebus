<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\MessageBroker;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\Implementation\CommandBus\Response\NeverCommandResponsePromise;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\MessageBroker\MessagePublisher;

/**
 * From our command bus interface, catch messages and send them into
 * makinacorpus/message-broker message broker instead.
 */
final class MessagePublisherCommandBusAdapter implements CommandBus
{
    private MessagePublisher $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        // @todo Here, handle queue/routingKey.
        $this->messagePublisher->dispatch(Envelope::wrap($command));

        return new NeverCommandResponsePromise();
    }
}
