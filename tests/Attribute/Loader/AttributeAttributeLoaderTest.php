<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Attribute\Loader;

use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Loader\AttributeAttributeLoader;

final class AttributeAttributeLoaderTest extends AbstractAttributeLoaderTest
{
    protected function createAttributeLoader(): AttributeLoader
    {
        if (PHP_VERSION_ID < 80000) {
            self::markTestSkipped("Attribute policy loader can only work with PHP >= 8.0");
        }

        return new AttributeAttributeLoader();
    }
}
