<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attribute\Loader;

use MakinaCorpus\CoreBus\Attribute\Attribute;
use MakinaCorpus\CoreBus\Attribute\AttributeList;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Error\AttributeError;

final class AttributeAttributeLoader implements AttributeLoader
{
    public function __construct()
    {
        if (PHP_VERSION_ID < 80000) {
            throw new AttributeError("Attribute policy loader can only work with PHP >= 8.0");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromClass(string $className): AttributeList
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new AttributeError("Class does not exists: " . $className, 0, $e);
        }

        return new DefaultAttributeList(
            (function () use ($reflectionClass) {
                foreach ($reflectionClass->getAttributes() as $attribute) {
                    if (\in_array(Attribute::class, \class_implements($attribute->getName()))) {
                        yield $attribute->newInstance();
                    }
                }
            })()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromClassMethod(string $className, string $methodName): AttributeList
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new AttributeError(\sprintf("Class does not exist: %s", $className), 0, $e);
        }

        try {
            $reflectionMethod = $reflectionClass->getMethod($methodName);
        } catch (\ReflectionException $e) {
            throw new AttributeError(\sprintf("Class method does not exist: %s::%s", $className, $methodName), 0, $e);
        }

        return new DefaultAttributeList(
            (function () use ($reflectionMethod) {
                foreach ($reflectionMethod->getAttributes() as $attribute) {
                    if (\in_array(Attribute::class, \class_implements($attribute->getName()))) {
                        yield $attribute->newInstance();
                    }
                }
            })()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromFunction(string $functionName): AttributeList
    {
        try {
            $reflectionFunction = new \ReflectionFunction($functionName);
        } catch (\ReflectionException $e) {
            throw new AttributeError("Class does not exists: " . $functionName, 0, $e);
        }

        return new DefaultAttributeList(
            (function () use ($reflectionFunction) {
                foreach ($reflectionFunction->getAttributes() as $attribute) {
                    if (\in_array(Attribute::class, \class_implements($attribute->getName()))) {
                        yield $attribute->newInstance();
                    }
                }
            })()
        );
    }
}
