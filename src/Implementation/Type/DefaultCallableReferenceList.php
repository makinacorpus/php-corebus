<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\Type;

final class DefaultCallableReferenceList implements CallableReferenceList
{
    private ?string $parameterInterfaceName;
    private bool $allowMultiple;
    /** @var array<string,CallableReference[]> */
    private array $references = [];

    public function __construct(?string $parameterInterfaceName, bool $allowMultiple)
    {
        $this->parameterInterfaceName = $parameterInterfaceName;
        $this->allowMultiple = $allowMultiple;
    }

    public function appendFromClass(
        string $handlerClassName,
        ?string $handlerServiceId = null
    ): void {
        foreach (self::findHandlerMethods(
            $handlerClassName,
            $handlerServiceId,
            $this->parameterInterfaceName
        ) as $reference) {
            $this->append($reference);
        }
    }

    /**
     * @return null|CallableReference
     */
    public function first(string $className): ?CallableReference
    {
        return $this->references[$className][0] ?? null;
    }

    /**
     * @return CallableReference[]
     */
    public function all(string $className): iterable
    {
        return $this->references[$className] ?? [];
    }

    private function append(CallableReference $reference): void
    {
        $existing = $this->references[$reference->className][0] ?? null;

        if ($existing && !$this->allowMultiple) {
            \assert($existing instanceof CallableReference);

            throw new \LogicException(\sprintf(
                "Handler for command class %s is already defined using %s::%s, found %s::%s",
                $reference->className,
                $existing->serviceId,
                $existing->methodName,
                $reference->serviceId,
                $reference->methodName
            ));
        }

        $this->references[$reference->className][] = $reference;
    }

    /**
     * Here lies magic, beware.
     *
     * This also is used by the compilation mecanism that creates cache.
     */
    public static function findHandlerMethods(
        string $handlerClassName,
        ?string $handlerServiceId = null,
        ?string $parameterInterfaceName = null
    ): iterable {
        $class = new \ReflectionClass($handlerClassName);
        $normalizedHandlerClassName = $class->getName();

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            \assert($method instanceof \ReflectionMethod);

            if (
                $method->isStatic() ||
                !$method->isPublic() ||
                $method->isConstructor() ||
                $method->isDestructor() ||
                $method->getDeclaringClass()->getName() !== $normalizedHandlerClassName
                // @todo Temporary, make this better
                || $method->getName() === 'setDomainLogger'
            ) {
                continue;
            }

            $parameters = $method->getParameters();
            if (1 !== \count($parameters)) {
                continue;
            }

            $parameter = \reset($parameters);
            \assert($parameter instanceof \ReflectionParameter);

            if (!$parameter->hasType()) {
                continue;
            }

            $type = $parameter->getType();
            \assert($type instanceof \ReflectionType);

            if ($type->isBuiltin()) {
                continue;
            }

            $parameterClassName = $type->getName();

            if (!$parameterInterfaceName || (
                \class_exists($parameterInterfaceName) && \in_array($parameterInterfaceName, \class_parents($parameterClassName))
            ) || (
                \interface_exists($parameterInterfaceName) && \in_array($parameterInterfaceName, \class_implements($parameterClassName))
            )) {
                yield new CallableReference($parameterClassName, $method->getName(), $handlerServiceId);
            }
        }
    }
}
