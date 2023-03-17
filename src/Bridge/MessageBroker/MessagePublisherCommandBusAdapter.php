<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\MessageBroker;

use MakinaCorpus\CoreBus\Attr\RoutingKey;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Response\NeverCommandResponsePromise;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\Message\Property;
use MakinaCorpus\MessageBroker\MessagePublisher;

/**
 * From our command bus interface, catch messages and send them into
 * makinacorpus/message-broker message broker instead.
 */
final class MessagePublisherCommandBusAdapter implements CommandBus
{
    private AttributeLoader $attributeLoader;
    private MessagePublisher $messagePublisher;

    public function __construct(MessagePublisher $messagePublisher)
    {
        $this->attributeLoader = new AttributeLoader();
        $this->messagePublisher = $messagePublisher;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $routingKey = null;

        if ($command instanceof Envelope) {
            $message = $command->getMessage();
            $routingKey = $command->getProperty(Property::ROUTING_KEY);
        } else {
            $message = $command;
        }

        if (!$routingKey) {
            if ($attribute = $this->attributeLoader->firstFromClass($message, RoutingKey::class)) {
                \assert($attribute instanceof RoutingKey);
                $routingKey = $attribute->getRoutingKey();
            }
        }

        $envelope = Envelope::wrap($command);
        if ($routingKey) {
            $envelope = $envelope->withProperties(['routing_key' => $routingKey]);
        }

        $this->messagePublisher->dispatch($envelope, $routingKey);

        return new NeverCommandResponsePromise($envelope->getProperties());
    }
}
