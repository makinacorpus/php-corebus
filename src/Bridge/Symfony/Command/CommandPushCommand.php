<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\Command;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\Normalization\NameMap;
use MakinaCorpus\Normalization\Serializer;
use MakinaCorpus\Normalization\NameMap\DefaultNameMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
final class CommandPushCommand extends Command
{
    protected static $defaultName = 'corebus:push';

    private CommandBus $asyncCommandBus;
    private SynchronousCommandBus $syncCommandBus;
    private Serializer $serializer;
    private NameMap $nameMap;

    public function __construct(
        CommandBus $asyncCommandBus,
        SynchronousCommandBus $syncCommandBus,
        Serializer $serializer,
        ?NameMap $nameMap = null
    ) {
        parent::__construct();

        $this->asyncCommandBus = $asyncCommandBus;
        $this->syncCommandBus = $syncCommandBus;
        $this->serializer = $serializer;
        $this->nameMap = $nameMap ?? new DefaultNameMap();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Push a command into the dispatcher')
            ->addArgument('command-name', InputArgument::REQUIRED, 'Command name, usually a class name')
            ->addOption('content-type', 't', InputOption::VALUE_REQUIRED, 'Content type, must be a known type to the serializer component', 'json')
            ->addOption('content', 'c', InputOption::VALUE_REQUIRED, 'Content formatted using the given --content-type format (default is JSON)')
            ->addOption('async', 'a', InputOption::VALUE_NONE, 'Only pushes the event into the bus for later asynchronous execution')
            ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Normalization tag name (default is "command")')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $normalizationTag = $input->getOption('tag') ?? NameMap::TAG_COMMAND;
        $commandName = $input->getArgument('command-name');
        $format = $input->getOption('content-type');
        $data = $input->getOption('content');

        if (!$data) {
            // Attempt read from STDIN
            $output->writeln('Reading command from STDIN');
            $data = \stream_get_contents(STDIN);
            if (false === $data) {
                throw new \InvalidArgumentException("No content was provided, and STDIN could not be read");
            }
            if (!$data) {
                $output->writeln('No content found from STDIN');
            }
        }

        $className = $this->nameMap->toPhpType($commandName, $normalizationTag);

        if ($data) {
            $message = $this->serializer->unserialize($className, $format, $data);
        } else {
            if (!\class_exists($className)) {
                throw new \InvalidArgumentException(\sprintf("No content was provided, and I cannot instanciate the '%s' class", $className));
            }
            $message = new $className();
        }

        if ($input->getOption('async')) {
            $this->asyncCommandBus->dispatchCommand($message);
            $output->writeln(\sprintf("<info>Message '%s' has been pushed into the asynchronous command bus successfuly</info>", $commandName));
        } else {
            $this->syncCommandBus->dispatchCommand($message);
            $output->writeln(\sprintf("<info>Message '%s' has been processed successfuly</info>", $commandName));
        }

        return 0;
    }
}
