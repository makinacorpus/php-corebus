<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache;

use MakinaCorpus\CoreBus\Attr\AbstractHandlerAttribute;
use MakinaCorpus\CoreBus\Attr\CommandHandler;
use MakinaCorpus\CoreBus\Attr\EventListener;
use MakinaCorpus\CoreBus\Error\ConfigurationError;

/**
 * Parse classes to find command handler or event listener methods.
 *
 * This is slow code, you better not use it at runtime. In most scenarios
 * this will be warmed up during some cache rebuild operation.
 */
class ClassParser
{
    const TARGET_COMMAND_HANDLER = 'command_handler';
    const TARGET_EVENT_LISTENER = 'event_listener';

    private ?string $target = null;

    public function __construct(?string $target = null)
    {
        $this->target = $target;
    }

    /**
     * Proceed to class lookup, attempt to find handlers.
     *
     * @param string $className
     * @param bool $parseAllMethods
     *   If set to true all class methods will be parsed disregarding the
     *   absence of CommandHandler attribute on the class.
     *
     * @return CallableReference[]
     */
    public function lookup(string $className, bool $parseAllMethods = false): iterable
    {
        if (!\class_exists($className)) {
            throw new ConfigurationError(\sprintf("Class '%s' does not exist.", $className));
        }

        $refClass = new \ReflectionClass($className);

        if (!$parseAllMethods) {
            if ($this->hasAttribute($refClass)) {
                $parseAllMethods = true;
            }
        }

        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            \assert($method instanceof \ReflectionMethod);

            if ($method->isStatic() || $method->isConstructor() || $method->isDestructor() || $method->getDeclaringClass()->getName() !== $refClass->getName()) {
                continue;
            }

            // @todo Temporary, make this better
            if ($method->getName() === 'setDomainLogger') {
                continue;
            }

            $userTypes = [];
            $hasAttribute = false;

            foreach ($this->findAttributes($method) as $attribute) {
                $hasAttribute = true;

                $instance = $attribute->newInstance();
                \assert($instance instanceof AbstractHandlerAttribute);

                $reference = $this->isMethodEligible($className, $method, $instance->getTarget());

                if (\in_array($reference->className, $userTypes)) {
                    throw new ConfigurationError(\sprintf("Method '%s()' target type '%s' is defined more than once.", $method->name, $reference->className));
                }
                $userTypes[] = $reference->className;

                yield $reference;
            }

            if (!$hasAttribute && $parseAllMethods) {
                try {
                    yield $this->isMethodEligible($className, $method);
                } catch (ConfigurationError $e) {
                    // This is an optimistic lookup, errors are expected.
                    // Just ignore when reaching here.
                }
            }
        }
    }

    private function hasAttribute(/* \ReflectionClass|\ReflectionMethod */ $ref): bool
    {
        switch ($this->target) {
            case self::TARGET_COMMAND_HANDLER:
                return (bool) $ref->getAttributes(CommandHandler::class);
            case self::TARGET_EVENT_LISTENER:
                return (bool) $ref->getAttributes(EventListener::class);
            default:
                return $ref->getAttributes(CommandHandler::class) || $ref->getAttributes(EventListener::class);
        }
    }

    /** @return list<\ReflectionAttribute> */
    private function findAttributes(/* \ReflectionClass|\ReflectionMethod */ $ref): iterable
    {
        switch ($this->target) {
            case self::TARGET_COMMAND_HANDLER:
                yield from $ref->getAttributes(CommandHandler::class);
                break;
            case self::TARGET_EVENT_LISTENER:
                yield from $ref->getAttributes(EventListener::class);
                break;
            default:
                yield from $ref->getAttributes(CommandHandler::class);
                yield from $ref->getAttributes(EventListener::class);
                break;
        }
    }

    /**
     * Validate arbitrary method (without a user type provided).
     */
    private function isMethodEligible(string $serviceClassName, \ReflectionMethod $method, ?string $userType = null): CallableReference
    {
        $parameterMap = [];
        $totalParameterCount = 0;
        $methodName = $method->getName();
        $commandParameterName = null;
        $commandParameterType = null;

        foreach ($method->getParameters() as $parameter) {
            \assert($parameter instanceof \ReflectionParameter);
            $totalParameterCount++;

            if (!$parameter->hasType()) {
                continue;
            }
            $allowedTypes = $this->expandTypes($parameter->getType());
            if (!$allowedTypes) {
                // We cannot work without a type.
                continue;
            }

            $parameterName = $parameter->getName();
            $parameterMap[$parameterName] = $allowedTypes;

            if ($userType && $this->validateTypeCompatibility($allowedTypes, $userType)) {
                if ($commandParameterName) {
                    throw new ConfigurationError(\sprintf(
                        "Method '%s()' has more than one parameter matching the given target type '%s' ('\$%s' and '\$%s').",
                        $methodName,
                        $userType,
                        $commandParameterName,
                        $parameterName
                    ));
                }

                $commandParameterName = $parameterName;
                $commandParameterType = $userType;
            }
        }

        if ($commandParameterName && $commandParameterType) {
            return new CallableReference(
                $commandParameterType,
                $commandParameterName,
                $methodName,
                $serviceClassName,
                1 !== $totalParameterCount
            );
        }

        if (!$parameterMap) {
            throw new ConfigurationError(\sprintf(
                "Method '%s()' has no class or interface typed parameters, you must specify a target type on the target parameter.",
                $methodName
            ));
        } else if (!$userType) {
            if (1 !== \count($parameterMap)) {
                // More than one possible parameter, ambiguous.
                throw new ConfigurationError(\sprintf(
                    "Method '%s()' has more than one parameter, target type is not specified, cannot guess which one to use, you must specify at least the target parameter type or name.",
                    $methodName
                ));
            }

            // Use the first parameter, if there is only one type.
            foreach ($parameterMap as $parameterName => $allowedTypes) {
                if (1 < \count($allowedTypes)) {
                    throw new ConfigurationError(\sprintf(
                        "Method '%s()' parameter '\$%s' has more than one eligible types, using union types are unsupported yet.",
                        $methodName,
                        $parameterName
                    ));
                }

                return new CallableReference(
                    \reset($allowedTypes),
                    $parameterName,
                    $methodName,
                    $serviceClassName,
                    1 !== $totalParameterCount
                );
            }
        } else {
            // No typed match was found, infer by property name instead.
            foreach ($parameterMap as $parameterName => $allowedTypes) {
                if ($parameterName === $userType) {
                    if (1 < \count($allowedTypes)) {
                        throw new ConfigurationError(\sprintf(
                            "Method '%s()' parameter '\$%s' has more than one eligible types, using union types is unsupported yet.",
                            $methodName,
                            $parameterName
                        ));
                    }

                    return new CallableReference(
                        \reset($allowedTypes),
                        $parameterName,
                        $methodName,
                        $serviceClassName,
                        1 !== $totalParameterCount
                    );
                }
            }
        }

        throw new ConfigurationError(\sprintf(
            "Method '%s()' user given target '%s' type is neither an argument type or a parameter name.",
            $methodName,
            $userType
        ));
    }

    /**
     * Expand given type, ignores built-in, only allow class names.
     */
    private function expandTypes(\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return [];
            }
            return [$type->getName()];
        }

        if ($type instanceof \ReflectionUnionType) {
            $ret = [];
            foreach ($type->getTypes() as $subType) {
                foreach ($this->expandTypes($subType) as $name) {
                    $ret[] = $name;
                }
            }

            return \array_unique($ret);
        }

        @\trigger_error(\sprintf("'ReflectionType' subclass '%s' is not handled.", \get_class($type)), E_USER_WARNING);

        return [];
    }

    /**
     * Validate type compatibility with a given user type.
     */
    private function validateTypeCompatibility(array $allowedTypes, string $userType): bool
    {
        if (!$allowedTypes) {
            return false;
        }

        // Volontarily disable the "object" type. It would return always true
        // and create weird bugs in the upper algorithm.
        if ('object' === $userType) {
            return false;
        }
        // Shortcut for avoiding reflection.
        if (\in_array($userType, $allowedTypes)) {
            return true;
        }
        // If user type does not exist, this is probably a parameter name.
        if (!\class_exists($userType) && !\interface_exists($userType)) {
            return false;
        }

        // This may raise exceptions, but in theory, it should be tested
        // before calling this method. So any error raised here would simply
        // be a real bug in the caller.
        $userTypeRef = new \ReflectionClass($userType);

        foreach ($allowedTypes as $candidate) {
            $typeRef = new \ReflectionClass($candidate);

            // If the reference type (the parameter) is an interface, the user
            // type must be either the same interface, a child interface, or a
            // class implementing it.
            if ($typeRef->isInterface()) {
                if ($userTypeRef->isSubclassOf($typeRef->getName())) {
                    return true;
                }
            }

            // If reference type (the method parameter) is a class, the user
            // type must be the same or a subclass.
            if ($userTypeRef->isInterface() && $typeRef->isSubclassOf($userTypeRef->getName())) {
                return true;
            }
        }

        return false;
    }
}
