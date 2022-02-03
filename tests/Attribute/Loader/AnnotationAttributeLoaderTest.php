<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Attribute\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\AnnotationAttributeLoader;

final class AnnotationAttributeLoaderTest extends AbstractAttributeLoaderTest
{
    protected function createAttributeLoader(): AttributeLoader
    {
        AnnotationRegistry::registerLoader('class_exists');

        return new AnnotationAttributeLoader(
            new AnnotationReader()
        );
    }
}
