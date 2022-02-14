<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\CommandHandlerLocator;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\Implementation\CommandBus\Response\SynchronousCommandResponsePromise;
use MakinaCorpus\Message\Envelope;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Consumes all messages synchronously.
 */
final class MemoryCommandBus implements SynchronousCommandBus, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CommandHandlerLocator $handlerLocator;

    public function __construct(CommandHandlerLocator $handlerLocator)
    {
        $this->handlerLocator = $handlerLocator;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchCommand(object $command): CommandResponsePromise
    {
        $this->logger->debug("MemoryCommandBus: Received command: {command}", ['command' => $command]);

        try {
            if ($command instanceof Envelope) {
                $command = $command->getMessage();
            }

            return SynchronousCommandResponsePromise::success(
                ($this->handlerLocator->find($command))($command)
            );
        } catch (\Throwable $e) {
            $this->logger->error("MemoryCommandBus: Error while processing: {command}: {trace}", ['command' => $command, 'trace' => $this->normalizeExceptionTrace($e)]);

            throw $e;
        }
    }

    /**
     * Normalize exception trace.
     *
     * @codeCoverageIgnore
     */
    private function normalizeExceptionTrace(\Throwable $exception): string
    {
        $output = '';
        do {
            if ($output) {
                $output .= "\n";
            }
            $output .= \sprintf("%s: %s\n", \get_class($exception), $exception->getMessage());
            $output .= $exception->getTraceAsString();
        } while ($exception = $exception->getPrevious());

        return $output;
    }
}
