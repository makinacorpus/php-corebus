<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * CommandBus is the user facing message dispatcher, you send a message through
 * it, whereas CommandConsumer is the internal message consumer, the system
 * processes the message through it.
 */
interface CommandConsumer
{
    /**
     * Consume a message.
     */
    public function consumeCommand(object $command): CommandResponsePromise;
}
