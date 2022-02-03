<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

use MakinaCorpus\CoreBus\Attribute\Attribute;

/**
 * Common base implementation for handler attributes.
 */
abstract class AbstractHandlerAttribute implements Attribute
{
    private ?string $taget = null;

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
