<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Attribute\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\AnnotationAttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\ChainAttributeLoader;

final class ChainAttributeLoaderTest extends AbstractAttributeLoaderTest
{
    protected function createAttributeLoader(): AttributeLoader
    {
        AnnotationRegistry::registerLoader('class_exists');

        return new ChainAttributeLoader([
            new AnnotationAttributeLoader(
                new AnnotationReader()
            )
        ]);
    }
}
