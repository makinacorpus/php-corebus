<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\EventBus;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use MakinaCorpus\CoreBus\Attr\Aggregate;
use MakinaCorpus\CoreBus\Attr\NoAggregate;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\AnnotationAttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\AttributeAttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\ChainAttributeLoader;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * This class allows to replace your event bus during unit tests.
 *
 * This implementation will raise errors if event are missing the Aggregate()
 * attribute/annotation.
 */
final class AggregateTestingEventBus extends TestingEventBus
{
    private static ?AttributeLoader $attributeLoader = null;

    /**
     * Get (or create if it does not exist) the attribute loader.
     */
    private static function getAttributeLoader(): AttributeLoader
    {
        if (self::$attributeLoader) {
            return self::$attributeLoader;
        }

        $loaders = [];

        if (PHP_VERSION_ID >= 80000) {
            $loaders[] = new AttributeAttributeLoader();
        }

        if (\class_exists(AnnotationRegistry::class)) {
            AnnotationRegistry::registerLoader('class_exists');

            $loaders[] = new AnnotationAttributeLoader(
                new AnnotationReader()
            );
        }

        return self::$attributeLoader = new ChainAttributeLoader($loaders);
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        $found = self::getAttributeLoader()->loadFromClass(\get_class($event))->first(Aggregate::class);
        if (!$found) {
            $found = self::getAttributeLoader()->loadFromClass(\get_class($event))->first(NoAggregate::class);
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
