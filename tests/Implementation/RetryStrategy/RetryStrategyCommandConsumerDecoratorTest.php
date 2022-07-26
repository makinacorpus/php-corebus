<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\RetryStrategy;

use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\Error\DispatcherRetryableError;
use MakinaCorpus\CoreBus\Implementation\RetryStrategy\DefaultRetryStrategy;
use MakinaCorpus\CoreBus\Implementation\RetryStrategy\RetryStrategyCommandConsumerDecorator;
use MakinaCorpus\CoreBus\Tests\Implementation\Mock\MockCommandA;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\Message\Property;
use MakinaCorpus\MessageBroker\MessageConsumer;
use PHPUnit\Framework\TestCase;

final class RetryStrategyCommandConsumerDecoratorTest extends TestCase
{
    public function testProcessDoesNotAttemptRetryOnArbitraryException(): void
    {
        self::expectNotToPerformAssertions();

        $commandConsumer = new class () implements CommandConsumer
        {
            public function consumeCommand(object $command): CommandResponsePromise
            {
                throw new \DomainException();
            }
        };

        $commandConsumer = $this->decorate(
            $commandConsumer,
            static function (Envelope $envelope) {
               throw new \BadMethodCallException("Message should not have been retried.");
            },
            static function (Envelope $envelope) {
                // Do nothing.
            }
        );

        try {
            $commandConsumer->consumeCommand(new MockCommandA());
            self::fail();
        } catch (\DomainException $e) {}
    }

    public function testProcessAttempsRetryOnRetryableError(): void
    {
        $retries = [];

        $commandConsumer = new class () implements CommandConsumer
        {
            public function consumeCommand(object $command): CommandResponsePromise
            {
                throw new DispatcherRetryableError();
            }
        };

        $commandConsumer = $this->decorate(
            $commandConsumer,
            static function (Envelope $envelope) use (&$retries) {
                $retries[] = $envelope;
            },
            static function (Envelope $envelope) {
                throw new \BadMethodCallException("Message should be retried, not rejected.");
            }
        );

        $sentMessage = new MockCommandA();

        try {
            $commandConsumer->consumeCommand($sentMessage);
            self::fail();
        } catch (DispatcherRetryableError $e) {}

        self::assertCount(1, $retries);

        $envelope = $retries[0];
        \assert($envelope instanceof Envelope);

        self::assertSame($sentMessage, $envelope->getMessage());
        self::assertSame("1", $envelope->getProperty(Property::RETRY_COUNT));
        self::assertSame("100", $envelope->getProperty(Property::RETRY_DELAI));
        self::assertSame("4", $envelope->getProperty(Property::RETRY_MAX));
    }

    public function testProcessDoesNotAttemptRetryWhenMaxReached(): void
    {
        self::expectNotToPerformAssertions();

        $commandConsumer = new class () implements CommandConsumer
        {
            public function consumeCommand(object $command): CommandResponsePromise
            {
                throw new \DomainException();
            }
        };

        $commandConsumer = $this->decorate(
            $commandConsumer,
            static function () { throw new DispatcherRetryableError(); },
            static function (Envelope $envelope) {
                // Do nothing.
            }
        );

        $sentEnvelope = Envelope::wrap(new \DateTimeImmutable(), [
            Property::RETRY_MAX => 4,
            Property::RETRY_COUNT => 4,
        ]);

        try {
            $commandConsumer->consumeCommand($sentEnvelope);
            self::fail();
        } catch (\DomainException $e) {}
    }

    private function decorate(CommandConsumer $decorated, callable $retryCallback, callable $rejectCallback): CommandConsumer
    {
        return new RetryStrategyCommandConsumerDecorator(
            $decorated,
            new DefaultRetryStrategy(),
            new class ($retryCallback, $rejectCallback) implements MessageConsumer
            {
                private $retryCallback;
                private $rejectCallback;

                public function __construct(callable $retryCallback, callable $rejectCallback)
                {
                    $this->retryCallback = $retryCallback;
                    $this->rejectCallback = $rejectCallback;
                }

                public function get(): ?Envelope
                {
                    throw new \BadMethodCallException("We are not testing this.");
                }

                public function ack(Envelope $envelope): void
                {
                    throw new \BadMethodCallException("We are not testing this.");
                }

                public function reject(Envelope $envelope, ?\Throwable $exception = null): void
                {
                    if ($envelope->hasProperty(Property::RETRY_COUNT)) {
                        ($this->retryCallback)($envelope);
                    } else {
                        ($this->rejectCallback)($envelope);
                    }
                }
            }
        );
    }
}
