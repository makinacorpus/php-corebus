<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Tell the event bus which aggregate stream this event is about.
 *
 * A stream is identified by its identifier, mostly an UUID, and an aggregate
 * type. The aggregate type is mandatory only for events which start a new
 * stream (UUID is always the only required identifier).
 *
 * Usage:
 *   #[Aggregate("eventPropertyName", "AggregateTypeAsString")]
 *   #[Aggregate("eventPropertyName", SomeClass::class)]
 *   #[Aggregate("eventPropertyName")]
 */
#[\Attribute]
final class Aggregate extends DomainEvent
{
    private string $aggregateIdPropertyName;
    private ?string $aggregateType = null;

    public function __construct(string $aggregateIdPropertyName, ?string $aggregateType = null)
    {
        $this->aggregateIdPropertyName = $aggregateIdPropertyName;
        $this->aggregateType = $aggregateType;
    }

    public function getAggregateIdPropertyName(): string
    {
        return $this->aggregateIdPropertyName;
    }

    public function getAggregateType(): ?string
    {
        return $this->aggregateType;
    }

    public function findAggregateId(object $object)
    {
        return $this->getValueFrom($object, $this->aggregateIdPropertyName);
    }

    private function getValueFrom(object $object, string $propertyName)
    {
        if ($value = $this->getValueFromProperty($object, $propertyName)) {
            return $value;
        }
        return $this->getValueFromMethod($object, $propertyName);
    }

    private function getValueFromProperty(object $object, string $propertyName)
    {
        try {
            $ref = new \ReflectionProperty($object, $propertyName);

            if ($ref->isPublic()) {
                return $object->{$propertyName};
            }

            return (\Closure::bind(
                fn ($victim) => $victim->{$propertyName},
                $object,
                \get_class($object)
            ))($object);

        } catch (\ReflectionException $e) {
            return null; // Property does not exist, fallback.
        }
    }

    private function getValueFromMethod(object $object, string $methodName)
    {
        try {
            $ref = new \ReflectionMethod($object, $methodName);

            // We can call the method nly if all parameters are optional.
            foreach ($ref->getParameters() as $parameter) {
                if (!$parameter->isOptional()) {
                    return null;
                }
            }

            if ($ref->isPublic()) {
                return $object->{$methodName}();
            }

            return (\Closure::bind(
                fn ($victim) => $victim->{$methodName}(),
                $object,
                \get_class($object)
            ))($object);

        } catch (\ReflectionException $e) {
            return null; // Method does not exist, fallback.
        }
    }
}
