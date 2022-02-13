<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\RetryStrategy;

use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\Message\Property;
use MakinaCorpus\MessageBroker\MessageBroker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * From our command bus interface, catch messages and send them into
 * makinacorpus/message-broker message broker instead.
 */
final class RetryStrategyCommandBusDecorator implements SynchronousCommandBus, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private SynchronousCommandBus $decorated;
    private RetryStrategy $retryStrategy;
    private MessageBroker $messageBroker;

    public function __construct(SynchronousCommandBus $decorated, RetryStrategy $retryStrategy, MessageBroker $messageBroker)
    {
        $this->decorated = $decorated;
        $this->retryStrategy = $retryStrategy;
        $this->messageBroker = $messageBroker;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $envelope = Envelope::wrap($command);

        try {
            $this->decorated->dispatchCommand($envelope);
        } catch (\Throwable $e) {
            if ($envelope->hasProperty(Property::RETRY_KILLSWITCH)) {
                $this->logger->debug("RetryStrategyCommandBusDecorator: Failure will not be retried, killed by killswitch.", ['exception' => $e]);

                throw $e;
            }

            $response = $this->retryStrategy->shouldRetry($envelope, $e);

            if ($response->shouldRetry()) {
                $this->logger->debug("RetryStrategyCommandBusDecorator: Failure is retryable.", ['exception' => $e]);

                $this->doRequeue($envelope, $response, $e);
            } else {
                $this->logger->debug("RetryStrategyCommandBusDecorator: Failure is not retryable.", ['exception' => $e]);

                $this->doReject($envelope, $e);
            }

            throw $e;
        }
    }

    /**
     * Requeue message if possible.
     */
    protected function doRequeue(Envelope $envelope, RetryStrategyResponse $response, ?\Throwable $exception = null): void
    {
        $count = (int) $envelope->getProperty(Property::RETRY_COUNT, "0");
        $delay = (int) $envelope->getProperty(Property::RETRY_DELAI, (string) $response->getDelay());
        $max = (int) $envelope->getProperty(Property::RETRY_MAX, (string) $response->getMaxCount());

        if ($count >= $max) {
            $this->doReject($envelope, $exception);

            return;
        }

        // Arbitrary delai. Yes, very arbitrary.
        $this->messageBroker->reject(
            $envelope->withProperties([
                Property::RETRY_COUNT => $count + 1,
                Property::RETRY_DELAI => $delay * ($count + 1),
                Property::RETRY_MAX => $max,
                Property::RETRY_REASON => $response->getReason(),
            ]),
            $exception
        );
    }

    /**
     * Reject message.
     */
    protected function doReject(Envelope $envelope, ?\Throwable $exception = null): void
    {
        // Rest all routing information, so that the broker will not take
        // those into account if some were remaining.
        $this->messageBroker->reject(
            $envelope->withProperties([
                Property::RETRY_COUNT => null,
                Property::RETRY_DELAI => null,
                Property::RETRY_MAX => null,
                Property::RETRY_REASON => null,
            ]),
            $exception
        );
    }
}
