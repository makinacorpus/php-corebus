<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

use MakinaCorpus\CoreBus\Attr\Loader\Attribute;

/**
 * Common base implementation for handler attributes.
 */
abstract class AbstractHandlerAttribute implements Attribute
{
    private ?string $target = null;

    public function __construct(?string $target = null)
    {
        $this->target = $target;
    }

    /**
     * Get target class or interface name.
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }
}
