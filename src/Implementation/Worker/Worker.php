<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\Worker;

use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\MessageBroker\MessageBroker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Worker implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const DEFAULT_IDLE_SLEEP_TIME = 1000000;

    private SynchronousCommandBus $commandBus;
    private MessageBroker $messageBroker;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private int $idleSleepTime;
    private ?\DateTimeInterface $startedAt = null;
    private bool $shouldStop = false;
    private int $limit = 0;
    private int $done = 0;

    public function __construct(SynchronousCommandBus $commandBus, MessageBroker $messageBroker, ?int $idleSleepTime = null, int $limit = 0)
    {
        $this->commandBus = $commandBus;
        $this->messageBroker = $messageBroker;
        $this->idleSleepTime = $idleSleepTime ?? self::DEFAULT_IDLE_SLEEP_TIME;
        $this->limit = $limit;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher ?? ($this->eventDispatcher = new EventDispatcher());
    }

    public function run(): void
    {
        if ($this->startedAt) {
            return;
        }

        $this->startedAt = new \DateTimeImmutable();
        $this->dispatch(WorkerEvent::start());

        while (!$this->shouldStop) {
            $message = $this->messageBroker->get();

            if (!$message) {
                // MessageBroker::get() could be blocking.
                // We need to handle stop before sleeping.
                if ($this->shouldStop) {
                    $this->dispatch(WorkerEvent::stop());

                    return;
                }

                $this->dispatch(WorkerEvent::idle());

                \usleep($this->idleSleepTime);
                continue;
            }

            $this->dispatch(WorkerEvent::next($message));

            try {
                $this->commandBus->dispatchCommand($message);
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->critical('Error happened during processing message: {exception}', ['exception' => $e]);
                }

                $this->messageBroker->reject($message, $e);

                // This is by design, dispatcher if correctly setup will handle
                // retry and reject by itself. This worker only functionnality
                // is about receiving a message and sending it to domain for
                // processing, retry or reject are business concerns and can't
                // be generalized by a technical layer such as this one.
                $this->dispatch(WorkerEvent::error());
            }

            $this->done++;

            if (0 < $this->limit && $this->done >= $this->limit) {
                $this->shouldStop = true;
            }
        }

        $this->dispatch(WorkerEvent::stop());
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * Dispatch event if listeners are attached.
     */
    private function dispatch(WorkerEvent $event): void
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch($event, $event->getEventName());
        }
    }
}
