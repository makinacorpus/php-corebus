<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\CommandHandlerLocator;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;
use MakinaCorpus\CoreBus\Implementation\Type\CallableReferenceList;
use MakinaCorpus\CoreBus\Implementation\Type\RuntimeCallableReferenceList;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ContainerCommandHandlerLocator implements CommandHandlerLocator
{
    private ?ServiceLocator $serviceLocator = null;
    private CallableReferenceList $referenceList;

    /**
     * @param array<string,string>|CallableReferenceList $references
     */
    public function __construct($references, ?ServiceLocator $serviceLocator = null)
    {
        $this->serviceLocator = $serviceLocator;

        if ($references instanceof CallableReferenceList) {
            $this->referenceList = $references;
        } else if (\is_array($references)) {
            $this->referenceList = new RuntimeCallableReferenceList(false);
            foreach ($references as $id => $className) {
                $this->referenceList->appendFromClass($className, $id);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find($command): callable
    {
        $reference = $this->referenceList->first(\get_class($command));

        if (!$reference) {
            throw CommandHandlerNotFoundError::fromCommand($command);
        }
        if (!$this->serviceLocator) {
            throw new \LogicException("Misinitialized event listener locator.");
        }

        $service = $this->serviceLocator->get($reference->serviceId);

        return static fn ($command) => $service->{$reference->methodName}($command);
    }
}
