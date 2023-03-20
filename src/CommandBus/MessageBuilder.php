<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\Error\CommandAlreadySentError;
use MakinaCorpus\CoreBus\Error\ConfigurationError;
use MakinaCorpus\Message\Property;
use MakinaCorpus\Message\PropertyBag;
use MakinaCorpus\Message\Identifier\MessageIdFactory;

/**
 * You may use this class in order to build a message with properties.
 */
class MessageBuilder
{
    private CommandBus $commandBus;
    private PropertyBag $properties;
    private mixed $command;
    private bool $sent = false;

    public function __construct(CommandBus $commandBus, object $command)
    {
        $this->commandBus = $commandBus;
        $this->command = $command;
        $this->properties = new PropertyBag();
    }

    /**
     * Set routing key (queue to send to).
     *
     * @return $this
     */
    public function routingKey(?string $routingKey): self
    {
        $this->properties->set(Property::ROUTING_KEY, $routingKey);

        return $this;
    }

    /**
     * Alias of routingKey().
     *
     * @return $this
     */
    public function queue(?string $routingKey): self
    {
        $this->routingKey($routingKey);

        return $this;
    }

    /**
     * Set a reply to header.
     *
     * You may set an explicit queue name for response, yet it is not
     * recommended. If you leave the queue name empty, the command bus
     * will generate one for you, and give you the resulting queue name
     * in the returned promise, under the 'reply-to' property name.
     *
     * Generating a queue name will also enfore a message identifier.
     *
     * @return $this
     */
    public function replyTo(bool $toggle = true, ?string $queue = null): self
    {
        if ($toggle) {
            if ($queue) {
                $this->properties->set(Property::REPLY_TO, $queue);
            } else {
                if ($this->properties->has(Property::MESSAGE_ID)) {
                    $messageId = $this->properties->get(Property::MESSAGE_ID);
                } else {
                    $messageId = MessageIdFactory::generate()->toString();
                    $this->properties->set(Property::MESSAGE_ID, $messageId);
                }
                $this->properties->set(Property::REPLY_TO, 'corebus.reply-to.' . $messageId);
            }
        } else {
            if (!$queue) {
                throw new ConfigurationError(\sprintf("You should not set a reply-to queue name if you disable reply-to."));
            }
            $this->properties->set(Property::REPLY_TO, null);
        }

        return $this;
    }

    /**
     * Dispatch the message via the commande bus.
     */
    public function dispatch(): CommandResponsePromise
    {
        if ($this->sent) {
            throw new CommandAlreadySentError("Command was already sent.");
        }
        $this->sent = true;

        return $this->commandBus->dispatchCommand($this->command, $this->properties->all());
    }
}
