<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attribute\Loader;

use Doctrine\Common\Annotations\Reader;
use MakinaCorpus\CoreBus\Attribute\Attribute;
use MakinaCorpus\CoreBus\Attribute\AttributeList;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Error\AttributeError;

final class AnnotationAttributeLoader implements AttributeLoader
{
    private Reader $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromClass(string $className): AttributeList
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new AttributeError("Class does not exist: " . $className, 0, $e);
        }

        return new DefaultAttributeList(
            (function () use ($reflectionClass) {
                foreach ($this->reader->getClassAnnotations($reflectionClass) as $annotation) {
                    if ($annotation instanceof Attribute) {
                        yield $annotation;
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
                foreach ($this->reader->getMethodAnnotations($reflectionMethod) as $annotation) {
                    if ($annotation instanceof Attribute) {
                        yield $annotation;
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
        // Doctrine annotations cannot read annotations from a function.
        // We do not raise an exception because this loader could be chained
        // and we don't want it to crash if another implementation can return
        // something instead.
        return new DefaultAttributeList();
    }
}
