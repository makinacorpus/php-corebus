<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attribute;

interface AttributeLoader
{
    /**
     * Load policies from class.
     */
    public function loadFromClass(string $className): AttributeList;

    /**
     * Load policies from class method.
     */
    public function loadFromClassMethod(string $className, string $methodName): AttributeList;

    /**
     * Load policies from function.
     */
    public function loadFromFunction(string $functionName): AttributeList;
}
