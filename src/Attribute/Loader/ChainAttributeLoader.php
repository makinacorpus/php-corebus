<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attribute\Loader;

use MakinaCorpus\CoreBus\Attribute\AttributeList;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;

final class ChainAttributeLoader implements AttributeLoader
{
    /** @var AttributeLoader */
    private iterable $instances;

    /** @param AttributeLoader[] $instances */
    public function __construct(iterable $instances)
    {
        $this->instances = $instances;
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromClass(string $className): AttributeList
    {
        return new DefaultAttributeList(
            (function () use ($className) {
                foreach ($this->instances as $instance) {
                    \assert($instance instanceof AttributeLoader);
                    yield from $instance->loadFromClass($className);
                }
            })()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromClassMethod(string $className, string $methodName): AttributeList
    {
        return new DefaultAttributeList(
            (function () use ($className, $methodName) {
                foreach ($this->instances as $instance) {
                    \assert($instance instanceof AttributeLoader);
                    yield from $instance->loadFromClassMethod($className, $methodName);
                }
            })()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromFunction(string $functionName): AttributeList
    {
        return new DefaultAttributeList(
            (function () use ($functionName) {
                foreach ($this->instances as $instance) {
                    \assert($instance instanceof AttributeLoader);
                    yield from $instance->loadFromFunction($functionName);
                }
            })()
        );
    }
}
