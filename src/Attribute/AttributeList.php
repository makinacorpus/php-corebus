<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attribute;

/**
 * Set of loaded attribute.
 */
final class AttributeList implements \IteratorAggregate
{
    private array $attributes = [];

    public function __construct(iterable $attributes)
    {
        // We need it to be rewindable anyway so let's computed everything
        // once for all and be happy with it.
        foreach ($attributes as $attribute) {
            \assert($attribute instanceof Attribute);
            $this->attributes[\get_class($attribute)][] = $attribute;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->attributes as $name => $attributes) {
            yield from $attributes;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): ?iterable
    {
        return $this->attributes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function first(string $name): ?Attribute
    {
        if (isset($this->attributes[$name])) {
            return \reset($this->attributes[$name]);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return !empty($this->attributes[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $name): int
    {
        if (isset($this->attributes[$name])) {
            return \count($this->attributes[$name]);
        }
        return 0;
    }
}
