<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\Type;

use MakinaCorpus\CoreBus\Attr\CommandHandler;
use MakinaCorpus\CoreBus\Attr\EventListener;
use MakinaCorpus\CoreBus\Error\ConfigurationError;
use MakinaCorpus\CoreBus\Attr\AbstractHandlerAttribute;

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

                if ($target = $instance->getTarget()) {
                    if (\in_array($target, $userTypes)) {
                        throw new ConfigurationError(\sprintf("Method '%s()' target type '%s' is defined more than once.", $method->name, $target));
                    }
                    $userTypes[] = $target;
                }
            }

            if ($hasAttribute) {
                if ($userTypes) {
                    foreach ($userTypes as $userType) {
                        yield $this->isMethodEligible($className, $method, $userType);
                    }
                } else {
                    yield $this->isMethodEligible($className, $method);
                }
            } else if ($parseAllMethods) {
                try {
                    yield $this->isMethodEligible($className, $method);
                } catch (ConfigurationError $e) {
                    // This is an optimistic lookup, errors are expected.
                    // Just ignore when reaching here.
                }
            }
        }
    }

    /** @return list<CommandHandler|EventListener> */
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

    /** @return list<CommandHandler|EventListener> */
    private function findAttributes(/* \ReflectionClass|\ReflectionMethod */ $ref): iterable
    {
        switch ($this->target) {
            case self::TARGET_COMMAND_HANDLER:
                yield from $ref->getAttributes(CommandHandler::class);
            case self::TARGET_EVENT_LISTENER:
                yield from $ref->getAttributes(EventListener::class);
            default:
                yield from $ref->getAttributes(CommandHandler::class);
                yield from $ref->getAttributes(EventListener::class);
        }
    }

    /**
     * Validate arbitrary method (without a user type provided).
     */
    private function isMethodEligible(string $serviceClassName, \ReflectionMethod $method, ?string $userType = null): CallableReference
    {
        if ($userType && !\class_exists($userType) && ! \interface_exists($userType)) {
            throw new ConfigurationError(\sprintf("Method '%s()' user type '%s' is not a class or an interface.", $method->name, $userType));
        }

        $parameters = $method->getParameters();
        if (1 !== \count($parameters)) {
            throw new ConfigurationError(\sprintf("Method '%s()' has more than one parameter.", $method->name));
        }

        $parameter = \reset($parameters);
        \assert($parameter instanceof \ReflectionParameter);

        if (!$parameter->hasType()) {
            if ($userType) {
                return new CallableReference($userType, $method->getName(), $serviceClassName);
            }
            throw new ConfigurationError(\sprintf("Method '%s()' first parameter has no type.", $method->name));
        }

        $type = $parameter->getType();
        \assert($type instanceof \ReflectionType);

        if ($type->isBuiltin()) {
            throw new ConfigurationError(\sprintf("Method '%s()' first parameter type is not a class or interface name.", $method->name));
        }

        // We cannot deal with union types right now. Maybe later.
        if ($type instanceof \ReflectionUnionType) {
            throw new ConfigurationError(\sprintf("Method '%s()' we do not support union types for handlers or listeners yet: '%s' given.", $method->name, $type));
        }

        if ($userType) {
            if (!$this->validateTypeCompatibility($type, $userType)) {
                throw new ConfigurationError(\sprintf("Method '%s()' user given type '%s' is not compatible with first parameter type '%s'.", $method->name, $userType, $type));
            }
            return new CallableReference($userType, $method->getName(), $serviceClassName);
        }

        return new CallableReference((string) $type, $method->getName(), $serviceClassName);
    }

    /**
     * Validate type compatibility with a given user type.
     */
    private function validateTypeCompatibility(\ReflectionType $type, string $userType): bool
    {
        if ($type instanceof \ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return false;
            }

            // Type are the same.
            if ($type->getName() === \ltrim($userType, '\\')) {
                return true;
            }

            // Allow "object" typing, weird but fine. It should not happend
            // thought since it would be a "catch all" handler. Nobody wants
            // to catch all, it would be terrible for performances.
            if ('object' === $userType) {
                return true;
            }

            $typeRef = new \ReflectionClass($type->getName());
            // This may raise exceptions, but in theory, it should be tested
            // before calling this method. So any error raised here would simply
            // be a real bug in the caller.
            $userTypeRef = new \ReflectionClass($userType);

            // If the reference type (the parameter) is an interface, the user
            // type must be either the same interface, a child interface, or a
            // class implementing it.
            if ($typeRef->isInterface()) {
                return $userTypeRef->isSubclassOf($typeRef->getName());
            }

            // If reference type (the method parameter) is a class, the user
            // type must be the same or a subclass.
            return !$userTypeRef->isInterface() && $userTypeRef->isSubclassOf($typeRef->getName());
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $subType) {
                if ($this->validateTypeCompatibility($subType, $userType)) {
                    return true;
                }
            }
            return false;
        }

        @\trigger_error(\sprintf("'ReflectionType' subclass '%s' is not handled.", \get_class($type)), E_USER_WARNING);

        return false;
    }
}
