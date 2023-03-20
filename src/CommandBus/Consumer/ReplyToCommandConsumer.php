<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Consumer;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\Message\Envelope;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Decorator that sends responses to the reply-to queue if any provided.
 */
final class ReplyToCommandConsumer implements CommandConsumer, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CommandBus $commandBus;
    private CommandConsumer $decorated;

    public function __construct(CommandConsumer $decorated, CommandBus $commandBus)
    {
        $this->decorated = $decorated;
        $this->commandBus = $commandBus;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function consumeCommand(object $command): CommandResponsePromise
    {
        $response = $this->decorated->consumeCommand($command);

        if (!$command instanceof Envelope || !($replyToQueue = $command->getReplyTo())) {
            return $response;
        }

        $value = $response->get();
        if (null === $value) {
            $this->logger->notice("ReplyToCommandConsumer: cannot reply-to {queue}, return value is null", ['queue' => $replyToQueue]);

            return $response;
        }

        if (!\is_object($value)) {
            $this->logger->warning("ReplyToCommandConsumer: cannot reply-to {queue} return value is not an object", ['queue' => $replyToQueue]);

            return $response;
        }

        $this->commandBus->create($value)->routingKey($replyToQueue)->dispatch();

        return $response;
    }
}
