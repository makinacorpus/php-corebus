<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Bus;

use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\AbstractContainerCallableLocator;
use MakinaCorpus\CoreBus\Cache\CallableReference;
use MakinaCorpus\CoreBus\EventBus\EventListenerLocator;

final class ContainerEventListenerLocator extends AbstractContainerCallableLocator implements EventListenerLocator
{
    /**
     * {@inheritdoc}
     */
    protected function allowMultiple(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function find(object $event): iterable
    {
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

                yield $this->createCallable($reference);
            }
        }
    }
}
