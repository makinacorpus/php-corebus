<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr\Loader;

final class AttributeLoader
{
    /**
     * Optimized faster version of loadFromClass() that does not instanciate
     * the attribute and do any iteration.
     *
     * @param string|string[] $attributeName
     *   If an array, return true if any of the attributes exists.
     */
    public function classHas(/* string|object */ $className, $attributeName): bool
    {
        $className = \is_object($className) ? \get_class($className) : $className;

        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new AttributeError("Class does not exists: " . $className, 0, $e);
        }

        if (\is_array($attributeName)) {
            foreach ($attributeName as $name) {
                if (!empty($reflectionClass->getAttributes($name))) {
                    return true;
                }
            }

            return false;
        }

        return !empty($reflectionClass->getAttributes($attributeName));
    }

    /**
     * {@inheritdoc}
     */
    public function firstFromClass(/* string|object */ $className, $attributeName): ?object
    {
        $className = \is_object($className) ? \get_class($className) : $className;

        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new AttributeError("Class does not exists: " . $className, 0, $e);
        }

        foreach ($reflectionClass->getAttributes($attributeName) as $attribute) {
            return $attribute->newInstance();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromClass(/* string|object */ $className): AttributeList
    {
        $className = \is_object($className) ? \get_class($className) : $className;

        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new AttributeError("Class does not exists: " . $className, 0, $e);
        }

        return new AttributeList(
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
    public function loadFromClassMethod(/* string|object */ $className, string $methodName): AttributeList
    {
        $className = \is_object($className) ? \get_class($className) : $className;

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

        return new AttributeList(
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

        return new AttributeList(
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
