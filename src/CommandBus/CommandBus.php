<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * CommandBus is the user facing message dispatcher, you send a message through
 * it, whereas CommandConsumer is the internal message consumer, the system
 * processes the message through it.
 */
interface CommandBus
{
    /**
     * Response may or may not be returned by command. Moreover, dispatching
     * can be delayed or sent asynchronously, case in which Reponse will not
     * be returned.
     */
    public function dispatchCommand(object $command, ?array $properties = null): CommandResponsePromise;

    /**
     * Create message builder.
     */
    public function create(object $command): MessageBuilder;
}
