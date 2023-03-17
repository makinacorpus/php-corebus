<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\CommandBus\Response;

use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Properties;

/**
 * Response for bus that cannot poll handler result.
 */
abstract class AbstractCommandResponsePromise implements CommandResponsePromise
{
    private Properties $properties;

    public function __construct(?array $properties = null)
    {
        $this->properties = new Properties($properties);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): Properties
    {
        return $this->properties;
    }
}
