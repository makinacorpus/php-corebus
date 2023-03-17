<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Consumer;

use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Response\NeverCommandResponsePromise;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class NullCommandConsumer implements CommandConsumer, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function consumeCommand(object $command): CommandResponsePromise
    {
        $this->logger->debug("NullCommandConsumer: Received command for consumption: {command}, dropping", ['command' => $command]);

        return new NeverCommandResponsePromise();
    }
}