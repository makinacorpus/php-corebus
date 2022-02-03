<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\EventBus;

use MakinaCorpus\CoreBus\EventBus\EventListenerLocator;
use MakinaCorpus\CoreBus\Implementation\Type\CallableReference;
use MakinaCorpus\CoreBus\Implementation\Type\CallableReferenceList;
use MakinaCorpus\CoreBus\Implementation\Type\RuntimeCallableReferenceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class ContainerEventListenerLocator implements EventListenerLocator, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private CallableReferenceList $referenceList;

    /**
     * @param array<string,string>|CallableReferenceList $references
     */
    public function __construct($references)
    {
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
        $references = $this->referenceList->all(\get_class($event));

        foreach ($references as $reference) {
            \assert($reference instanceof CallableReference);

            $service = $this->container->get($reference->serviceId);

            yield static fn (object $command) => $service->{$reference->methodName}($command);
        }
    }
}
