<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Bus;

use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\Message\Envelope;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class PassthroughCommandBus extends AbstractCommandBus implements SynchronousCommandBus, LoggerAwareInterface
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
    public function dispatchCommand(object $command, ?array $properties = null): CommandResponsePromise
    {
        $this->logger->debug("PassthroughCommandBus: Passing command to consumer: {command}", ['command' => $command]);

        return $this->commandConsumer->consumeCommand(
            // Wrap properties ensures that all properties added by decorators
            // end up in the envelope and allows command consumers to read them.
            // Information such as the user_id when plugged into Symfony can be
            // critical to have.
            Envelope::wrap($command, $properties ?? [])
        );
    }
}
