<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attribute;

/**
 * Set of loaded attribute.
 */
interface AttributeList extends \Traversable
{
    /**
     * Get attributes matching the given name.
     *
     * @return Attribute[]
     */
    public function get(string $name): ?iterable;

    /**
     * Get first attribute matching the name, order is not guaranteed.
     */
    public function first(string $name): ?Attribute;

    /**
     * As at least one attribute matching the given name.
     */
    public function has(string $name): bool;

    /**
     * Count attributes matching the given name.
     */
    public function count(string $name): int;
}
