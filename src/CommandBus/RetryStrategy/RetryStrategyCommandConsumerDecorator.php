<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\RetryStrategy;

use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\Message\Property;
use MakinaCorpus\MessageBroker\MessageConsumer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * From our command bus interface, catch messages and send them into
 * makinacorpus/message-broker message broker instead.
 *
 * Because the retry strategy requires you to have the same MessageConsumer
 * instance the message originated from, it can't be used in a generic manner.
 * This component will never be auto-configured, but will be instanciated by
 * the worker instead in order to decorate its own logic.
 */
final class RetryStrategyCommandConsumerDecorator implements CommandConsumer, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CommandConsumer $decorated;
    private RetryStrategy $retryStrategy;
    private MessageConsumer $messageConsumer;

    public function __construct(CommandConsumer $decorated, RetryStrategy $retryStrategy, MessageConsumer $messageConsumer)
    {
        $this->decorated = $decorated;
        $this->retryStrategy = $retryStrategy;
        $this->messageConsumer = $messageConsumer;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function consumeCommand(object $command): CommandResponsePromise
    {
        $envelope = Envelope::wrap($command);
        $count = 0;
        $max = 0;

        for ($i = 0; $i < 10; ++$i) { // Avoid infinite loop.
            try {
                ++$count;

                return $this->decorated->consumeCommand($envelope);
            } catch (\Throwable $e) {
                if ($envelope->hasProperty(Property::RETRY_KILLSWITCH)) {
                    $this->logger->notice("RetryStrategyCommandConsumerDecorator: Failure will not be retried, killed by killswitch.", ['exception' => $e]);

                    throw $e;
                }

                $response = $this->retryStrategy->shouldRetry($envelope, $e);

                if ($response->shouldRetryWithoutRequeue()) {
                    if ($max < 1) {
                        $max = $response->getMaxCount();
                    }

                    if (!$max || $max <= $count) {
                        $this->logger->error("RetryStrategyCommandConsumerDecorator: Failure was locally retryable, failed {count} attempts.", ['exception' => $e, 'count' => $count]);

                        $this->doRequeue($envelope, $response, $e);
                    } else {
                        $this->logger->notice("RetryStrategyCommandConsumerDecorator: Failure is locally retryable, retrying ({count}/{max}).", ['exception' => $e, 'count' => $count + 1, 'max' => $max]);

                        continue; // Retry.
                    }
                }

                if ($response->shouldRetry()) {
                    $this->logger->error("RetryStrategyCommandConsumerDecorator: Failure is retryable.", ['exception' => $e]);

                    $this->doRequeue($envelope, $response, $e);
                } else {
                    $this->logger->error("RetryStrategyCommandConsumerDecorator: Failure is not retryable.", ['exception' => $e]);

                    $this->doReject($envelope, $e);
                }

                throw $e;
            }
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
        $this->messageConsumer->reject(
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
        $this->messageConsumer->reject(
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
