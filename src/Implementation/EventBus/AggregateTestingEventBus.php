<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\EventBus;

use MakinaCorpus\CoreBus\Attr\Aggregate;
use MakinaCorpus\CoreBus\Attr\NoAggregate;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * This class allows to replace your event bus during unit tests.
 *
 * This implementation will raise errors if event are missing the Aggregate()
 * attribute/annotation.
 */
final class AggregateTestingEventBus extends TestingEventBus
{
    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        $attributeLoader = new AttributeLoader();
        $found = $attributeLoader->loadFromClass(\get_class($event))->first(Aggregate::class);
        if (!$found) {
            $found = $attributeLoader->loadFromClass(\get_class($event))->first(NoAggregate::class);
            if (!$found) {
                throw new ExpectationFailedException(
                    \sprintf(
                        "Event class %s is missing the %s attribute or annotation.",
                        \get_class($event), Aggregate::class
                    )
                );
            }
        }

        parent::notifyEvent($event);
    }
}
