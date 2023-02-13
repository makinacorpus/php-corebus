<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Tell which routing_key to dispatch the command to.
 *
 * Usage:
 *   #[RoutingKey("some_queue_name")]
 */
#[\Attribute]
final class RoutingKey extends Command
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getRoutingKey(): string
    {
        return $this->name;
    }
}
