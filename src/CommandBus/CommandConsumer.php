<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * Consume messages.
 */
interface CommandConsumer
{
    /**
     * Consume a message.
     */
    public function consumeCommand(object $command): CommandResponsePromise;
}
