<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\Implementation\CommandBus\Response\NeverCommandResponsePromise;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class NullCommandBus implements SynchronousCommandBus, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $this->logger->debug("NullCommandBus: Received command for dispatch: {command}, dropping", ['command' => $command]);

        return new NeverCommandResponsePromise();
    }
}