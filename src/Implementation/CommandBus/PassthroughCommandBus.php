<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class PassthroughCommandBus implements SynchronousCommandBus, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CommandConsumer $commandConsumer;

    public function __construct(CommandConsumer $commandConsumer)
    {
        $this->commandConsumer = $commandConsumer;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $this->logger->debug("PassthroughCommandBus: Passing command to consumer: {command}, dropping", ['command' => $command]);

        return $this->commandConsumer->consumeCommand($command);
    }
}