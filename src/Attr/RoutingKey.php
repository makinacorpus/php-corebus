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
    private string $routingKey;

    public function __construct(string $routingKey)
    {
        $this->routingKey = $routingKey;
    }

    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}
