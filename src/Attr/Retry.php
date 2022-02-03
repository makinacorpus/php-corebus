<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Mark the commande as being retryable, and how many times it should be retry
 * in case of failure to execute.
 *
 * Usage:
 *   #[Retry(4)]
 *   #[Retry]
 */
#[\Attribute]
final class Retry extends Command
{
    private int $count;

    public function __construct(?int $count = null)
    {
        $this->count = $count ?? 3;
    }

    public function getRetryCount(): string
    {
        return $this->count;
    }
}
