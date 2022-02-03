<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Transaction;

/**
 * Provides a way to embed more than one command in a single transaction.
 *
 * Since that commands are supposedly atomic, and bus cannot guarante message
 * processing ordering we need to wrap a single transaction in a single command
 * envelope, this is this envelope.
 *
 * When passing a command within the multiple transaction which explicitly
 * disable transaction using an attribute, it will be ignored and the command
 * will be run within the transaction as well.
 *
 * Depending upon your serialization mechanism, you might need to write
 * additional code in your framework in order to support this object
 * deserialization, using the provided command type.
 */
final class MultiCommand implements \Traversable, \IteratorAggregate, \Countable
{
    /** @var object[] */
    private array $commands = [];

    public function __construct(iterable $commands)
    {
        foreach ($commands as $command) {
            if (!\is_object($command)) {
                throw new \InvalidArgumentException(\sprintf("Commands passed to %s::%s() must be object instances.", __CLASS__, __FUNCTION__));
            }
            $this->commands[] = $command;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): iterable
    {
        return (fn () => yield from $this->commands)();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->commands);
    }
}
