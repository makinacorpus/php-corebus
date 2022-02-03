<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attribute\Loader;

use MakinaCorpus\CoreBus\Attribute\AttributeList;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;

/**
 * @codeCoverageIgnore
 */
class NullAttributeLoader implements AttributeLoader
{
    /**
     * {@inheritdoc}
     */
    public function loadFromClass(string $className): AttributeList
    {
        return new DefaultAttributeList([]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromClassMethod(string $className, string $methodName): AttributeList
    {
        return new DefaultAttributeList([]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromFunction(string $functionName): AttributeList
    {
        return new DefaultAttributeList([]);
    }
}
