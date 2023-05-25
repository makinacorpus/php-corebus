<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\Command;

use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\RetryStrategy\RetryStrategy;
use MakinaCorpus\CoreBus\CommandBus\Worker\Worker;
use MakinaCorpus\CoreBus\CommandBus\Worker\WorkerEvent;
use MakinaCorpus\Message\Envelope;
use MakinaCorpus\MessageBroker\MessageConsumerFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

/**
 * @codeCoverageIgnore
 */
final class CommandWorkerCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'corebus:worker';

    private CommandConsumer $commandConsumer;
    private ?MessageConsumerFactory $messageConsumerFactory = null;
    private ?RetryStrategy $retryStrategy = null;
    private ?ServicesResetter $servicesResetter = null;

    public function __construct(
        CommandConsumer $commandConsumer,
        ?MessageConsumerFactory $messageConsumerFactory = null,
        ?RetryStrategy $retryStrategy = null,
        ?ServicesResetter $servicesResetter = null
    ) {
        parent::__construct();

        $this->commandConsumer = $commandConsumer;
        $this->messageConsumerFactory = $messageConsumerFactory;
        $this->retryStrategy = $retryStrategy;
        $this->servicesResetter = $servicesResetter;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Run bus worker daemon')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, "Number of messages to consume", null)
            // ->addOption('idle-sleep', 's', InputOption::VALUE_OPTIONAL, "Idle sleep time, in micro seconds", null)
            ->addOption('routing-key', 'r', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, "Queue in which to consume messages")
            ->addOption('memory-limit', 'm', InputOption::VALUE_OPTIONAL, "Maximum memory consumption; eg. 128M, 1G, ...", null)
            ->addOption('memory-leak', 'w', InputOption::VALUE_REQUIRED, "Memory leak warning threshold when consuming a message; eg. 128M, 1G, ...", '512K')
            ->addOption('time-limit', 't', InputOption::VALUE_OPTIONAL, "Maximum run time; eg. '1 hour', '2 minutes', ...", null)
            ->addOption('sleep-time', 's', InputOption::VALUE_OPTIONAL, "Sleep time when IDLE in microseconds", null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->messageConsumerFactory) {
            $output->writeln("<error>This command can only work if 'corebus.command_bus.adapter' is 'message_broker'.</error>");
        }

        $startedAt = new \DateTimeImmutable();
        $startedTimestamp = $startedAt->getTimestamp();
        $timeLimit = self::parseTime($input->getOption('time-limit'));
        $eventCountLimit = (int) $input->getOption('limit');
        $queueList = (array) $input->getOption('routing-key');

        if ($sleepTime = $this->parseInt($input->getOption('sleep-time'))) {
            $output->writeln(\sprintf("Sleep time set to %d ms", $sleepTime));
        }

        // Constraints and statistics.
        $memoryLimit = $this->computeMemoryLimit($input->getOption('memory-limit'), $output);
        $memoryLeakThreshold = self::parseSize($input->getOption('memory-leak'));
        $currentMemory = 0;
        $messageCount = 0;

        if (!$queueList) {
            $queueList = ['default'];
        }

        $output->writeln(\sprintf("Running bus worker consumming in '%s' routing key(s).", \implode("', '", $queueList)));

        $worker = new Worker($this->commandConsumer, $this->messageConsumerFactory->createConsumer($queueList), $sleepTime, $eventCountLimit);
        $worker->setLogger($this->logger);
        if ($this->retryStrategy) {
            $worker->setRetryStrategy($this->retryStrategy);
        }

        $handleTick = static function () use ($worker, $startedTimestamp, $memoryLimit, $timeLimit, $output, &$messageCount, &$currentMemory) {
            if ($memoryLimit && $memoryLimit <= \memory_get_usage(true)) {
                $output->writeln(\sprintf("Memory limit reached, stopping worker - CONSUMED %d messages - MEMORY %s bytes.", $messageCount, $currentMemory));
                $worker->stop();
            }
            if ($timeLimit && $timeLimit < time() - $startedTimestamp) {
                $output->writeln(\sprintf("Time limit reached, stopping worker - CONSUMED %d messages - MEMORY %s bytes.", $messageCount, $currentMemory));
                $worker->stop();
            }
        };

        $eventDispatcher = $worker->getEventDispatcher();

        $eventDispatcher->addListener(
            WorkerEvent::IDLE,
            function (WorkerEvent $event) use ($output, $handleTick, &$messageCount, &$currentMemory) {
                if ($output->isVeryVerbose()) {
                    $output->writeln(\sprintf("%s - IDLE received - CONSUMED %d messages - MEMORY %s bytes.", self::nowAsString(), $messageCount, $currentMemory));
                }
                $handleTick();
            }
        );

        $eventDispatcher->addListener(
            WorkerEvent::NEXT,
            function (WorkerEvent $event) use ($output, &$messageCount, &$currentMemory) {
                $messageCount++;
                if ($output->isVerbose()) {
                    $output->writeln(\sprintf("%s - NEXT message #%d: %s - MEMORY %d bytes.", self::nowAsString(), $messageCount, self::messageAsString($event->getMessage()), $currentMemory));
                }
            }
        );

        $eventDispatcher->addListener(
            WorkerEvent::DONE,
            function (WorkerEvent $event) use ($output, $handleTick, &$messageCount, &$currentMemory, $memoryLeakThreshold) {
                $previous = $currentMemory;
                $currentMemory = \memory_get_usage(true);
                if ($memoryLeakThreshold < ($currentMemory - $previous)) {
                    $output->writeln(\sprintf("%s - MEMORY LEAK while message #%d: %s (current consumption: %d bytes).", self::nowAsString(), $messageCount, self::messageAsString($event->getMessage()), $currentMemory));
                }
                $handleTick();
            }
        );

        if ($this->servicesResetter) {
            $eventDispatcher->addListener(WorkerEvent::DONE, fn () => $this->servicesResetter->reset());
        }

        $currentMemory = \memory_get_usage(true);
        $worker->run();

        return 0;
    }

    private static function computeMemoryLimit(?string $userInput, OutputInterface $output): int
    {
        $memoryLimit = self::parseSize($userInput);

        if ($memoryLimit) {
            $output->writeln(\sprintf("Memory limit set to %d bytes", $memoryLimit));

            return $memoryLimit;
        }

        // We REQUIRE a memory limit is set for the bus to gracefully exit
        // when we approach that limit: in case of memory leak on each
        // message, breaking memory limit during process will also break
        // the transaction, and may also cause monolog logging to be lost.
        // We do desesperatly need to avoid that at all cost.
        // Reserving 16M is a huge lot, and business process will probably
        // never consume that much in a single message (honest guess).
        if (($value = \ini_get('memory_limit')) && '-1' !== $value) {
            $memoryLimit = self::parseSize($value);

            if ($memoryLimit < 1024 * 1024 * 128) {
                // Less than 128M, we reserve a percentage.
                $memoryLimit = \round($memoryLimit * 0.9);
                $output->writeln(\sprintf("PHP memory limit is lower than 128M, reserving 10%%, memory limit set to %d bytes.", $memoryLimit));

                return (int) $memoryLimit;
            }

            // More than 128M, we reserve 16M.
            $memoryLimit -= 1024 * 1024 * 16;
            $output->writeln(\sprintf("PHP memory limit is higher than 128M, reserving 16M, memory limit set to %d bytes.", $memoryLimit));

            return $memoryLimit;
        }

        $output->writeln("<error>PHP as no memory_limit set, not setting any. Beware you might experience memory leaks, value is set to 1G.</error>");

        return 1024 * 1024 * 1024;
    }

    private static function nowAsString(): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s.uP');
    }

    private static function messageAsString($message): string
    {
        if (null === $message) {
            return "(null)";
        }
        if ($message instanceof Envelope) {
            return \sprintf("%s - %s", $message->getMessageId(), self::messageAsString($message->getMessage()));
        }
        if (\is_object($message)) {
            return \sprintf("%s", \get_class($message));
        }
        return \sprintf("%s (%s)", \gettype($message), (string)$message);
    }

    /**
     * Parse user input integer value.
     */
    private static function parseInt(?string $input): ?int
    {
        if ('' === $input || null === $input) {
            return null;
        }

        if (!\ctype_digit($input)) {
            throw new \InvalidArgumentException(\sprintf("Invalid integer: '%s'", $input));
        }

        return (int) $input;
    }

    /**
     * Parse user input time in seconds.
     */
    private static function parseTime(?string $input): ?int
    {
        if ('' === $input || null === $input) {
            return null;
        }

        $interval = \DateInterval::createFromDateString($input);

        if (!$interval) {
            throw new \InvalidArgumentException(\sprintf("Invalid interval: '%s'", $input));
        }

        $reference = new \DateTimeImmutable();

        return $reference->add($interval)->getTimestamp() - $reference->getTimestamp();
    }

    /**
     * Parse user input size in bytes.
     */
    private static function parseSize(?string $input): ?int
    {
        if ('' === $input || null === $input) {
            return null;
        }

        $value = \strtolower(\trim($input));
        $suffix = \substr($value, -1);
        $factor = 1;

        switch ($suffix) {
            case 'g':
                $value = \substr($value, 0, -1);
                $factor = 1024 * 1024 * 1024;
                break;

            case 'm':
                $value = \substr($value, 0, -1);
                $factor = 1024 * 1024;
                break;

            case 'k':
                $value = \substr($value, 0, -1);
                $factor = 1024;
                break;

            default:
                break;
        }

        if (!\ctype_digit($value)) {
            throw new InvalidArgumentException(\sprintf("Invalid number of bytes: '%s'", $input));
        }

        return ((int) $value) * $factor;
    }
}
