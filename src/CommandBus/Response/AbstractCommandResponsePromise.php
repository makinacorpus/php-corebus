<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Response;

use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\Message\PropertyBag;

/**
 * Response for bus that cannot poll handler result.
 */
abstract class AbstractCommandResponsePromise implements CommandResponsePromise
{
    private PropertyBag $properties;

    public function __construct(?array $properties = null)
    {
        $this->properties = new PropertyBag($properties);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): PropertyBag
    {
        return $this->properties;
    }
}
