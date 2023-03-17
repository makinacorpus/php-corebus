<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Bus;

use MakinaCorpus\CoreBus\Cache\CallableReference;
use MakinaCorpus\CoreBus\Cache\CallableReferenceList;
use MakinaCorpus\CoreBus\Cache\RuntimeCallableReferenceList;
use MakinaCorpus\CoreBus\EventBus\EventListenerLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ContainerEventListenerLocator implements EventListenerLocator
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
            $this->referenceList = new RuntimeCallableReferenceList(true);
            foreach ($references as $id => $className) {
                $this->referenceList->appendFromClass($className, $id);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find(object $event): iterable
    {
        if (!$this->serviceLocator) {
            throw new \LogicException("Misinitialized event listener locator.");
        }

        $candidates = [];
        $className = \get_class($event);

        // Allow event listeners to react using interfaces.
        foreach (\class_implements($className) as $interface) {
            $candidates[] = $interface;
        }

        // Allow event listeners to react using parent classes.
        do {
            $candidates[] = $className;
        } while ($className = \get_parent_class($className));

        foreach ($candidates as $candidate) {
            foreach ($this->referenceList->all($candidate) as $reference) {
                \assert($reference instanceof CallableReference);

                $service = $this->serviceLocator->get($reference->serviceId);

                yield static fn (object $command) => $service->{$reference->methodName}($command);
            }
        }
    }
}
