<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * CommandBus is the user facing message dispatcher, you send a message through
 * it, whereas CommandPublisher is the internal message publisher, the real bus
 * implementation that pushes a message away.
 */
interface CommandPubliser
{
    /**
     * Consume a message.
     */
    public function publishCommand(object $command): CommandResponsePromise;
}
