<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\MessageBroker;

use MakinaCorpus\CoreBus\Attr\RoutingKey;
use MakinaCorpus\CoreBus\Attr\Loader\AttributeLoader;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Bus\AbstractCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Response\NeverCommandResponsePromise;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\Message\Property;
use MakinaCorpus\MessageBroker\MessagePublisher;

/**
 * From our command bus interface, catch messages and send them into
 * makinacorpus/message-broker message broker instead.
 */
final class MessagePublisherCommandBusAdapter extends AbstractCommandBus implements CommandBus
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
    public function dispatchCommand(object $command, ?array $properties = null): CommandResponsePromise
    {
        $routingKey = null;

        $envelope = Envelope::wrap($command, $properties ?? []);
        $routingKey = $envelope->getProperty(Property::ROUTING_KEY);

        if (!$routingKey) {
            if ($attribute = $this->attributeLoader->firstFromClass($command, RoutingKey::class)) {
                \assert($attribute instanceof RoutingKey);
                $routingKey = $attribute->getRoutingKey();
                $envelope->setProperties(['routing_key' => $routingKey]);
            }
        }

        $this->messagePublisher->dispatch($envelope, $routingKey);

        return new NeverCommandResponsePromise($envelope->getPropertyBag()->all());
    }
}
