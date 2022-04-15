<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus;

use MakinaCorpus\CoreBus\Attr\NoTransaction;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Transaction\MultiCommand;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\Implementation\EventBus\EventBuffer;
use MakinaCorpus\CoreBus\Implementation\EventBus\EventBufferManager;
use MakinaCorpus\CoreBus\Implementation\Transaction\TransactionManager;
use MakinaCorpus\Message\Envelope;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Encapsulate command processing and event emition into a transaction.
 * If the transaction come to fail, emitted event buffer is discarded,
 * database transaction is rollbacked, nothing happened. The exception
 * will be throw anyway, if any other component is monitoring the bus
 * they will be able to catch it.
 *
 * This component doesn't log anything, everything related to log handling,
 * retry mechanism and monitoring should be done on decorator.
 *
 * Transactions can be disabled on a per-command basis using PHP attributes.
 * Please note that while transaction is disabled, event buffer remains
 * activated.
 * @todo
 *   Should we disable it as well and let event pass no matter what?
 */
final class TransactionalCommandBus implements SynchronousCommandBus, EventBus, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CommandBus $commandBus;
    private EventBus $internalEventBus;
    private EventBus $externalEventBus;
    private EventBufferManager $eventBufferManager;
    private TransactionManager $transactionManager;
    private ?EventBuffer $buffer = null;

    public function __construct(
        CommandBus $commandBus,
        EventBus $internalEventBus,
        EventBus $externalEventBus,
        EventBufferManager $eventBufferManager,
        TransactionManager $transactionManager
    ) {
        $this->commandBus = $commandBus;
        $this->internalEventBus = $internalEventBus;
        $this->externalEventBus = $externalEventBus;
        $this->eventBufferManager = $eventBufferManager;
        $this->transactionManager = $transactionManager;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $transaction = null;

        $this->buffer = $this->eventBufferManager->start();

        if ($multiple = $command instanceof MultiCommand) {
            $this->logger->notice("TransactionalCommandBus: Running {command} multi-command transaction.", ['command' => \get_class($command)]);
            $count = 0;
            $total = $command->count();
        } else {
            $count = 1; // Will not change.
            $total = 1;
        }

        try {
            if ($command instanceof Envelope) {
                $message = $command->getMessage();
            } else {
                $message = $command;
            }

            $disableTransaction = (!$multiple) && (new AttributeLoader())->loadFromClass($message)->has(NoTransaction::class);

            if ($disableTransaction) {
                $this->logger->notice("TransactionalCommandBus: Running {command} without transaction.", ['command' => \get_class($command)]);
                $response = $this->commandBus->dispatchCommand($command);
            } else {
                $transaction = $this->transactionManager->start();

                if ($multiple) {
                    foreach ($command as $child) {
                        $count++;
                        $response = $this->commandBus->dispatchCommand($child);
                    }
                } else {
                    $response = $this->commandBus->dispatchCommand($command);
                }

                $transaction->commit();
            }

            $this->flush();

            return $response;

        } catch (\Throwable $e) {
            $this->logger->error("Transaction failed at item {index}/{total}", ['index' => $count, 'total' => $total]);

            if ($transaction) {
                $transaction->rollback();
            }

            $this->discard();

            throw $e;

        } finally {
            if ($this->buffer) {
                $this->buffer->discard();
                $this->buffer = null;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        if (!$this->buffer) {
            $this->logger->error("TransactionalCommandBus: Receiving an event oustide of transaction, forwading.");
            $this->internalEventBus->notifyEvent($event);

            return;
        }

        $this->internalEventBus->notifyEvent($event);
        $this->buffer->add($event);
    }

    /**
     * Discard all events.
     */
    private function discard(): void
    {
        $this->logger->error("TransactionalCommandBus: Discarded {count} events.", ['count' => \count($this->buffer)]);

        $this->buffer->discard();
        $this->buffer = null;
    }

    /**
     * Send all events.
     */
    private function flush(): void
    {
        $this->logger->debug("TransactionalCommandBus: Will flush {count} events.", ['count' => \count($this->buffer)]);

        $errors = 0;
        $total = 0;

        foreach ($this->buffer->flush() as $event) {
            ++$total;
            try {
                $this->externalEventBus->notifyEvent($event);
            } catch (\Throwable $e) {
                ++$errors;
                $this->logger->error("TransactionalCommandBus: Error while event '{event}' flush.", ['event' => $event]);
            }
        }
        $this->buffer = null;

        $this->logger->debug("TransactionalCommandBus: Flushed {total} events, {error} errors.", ['total' => $total, 'error' => $errors]);
    }
}
