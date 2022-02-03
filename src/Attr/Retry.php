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
 *
 * @Annotation
 */
#[\Attribute]
final class Retry extends Command
{
    private int $count;

    public function __construct($count = null)
    {
        // Doctrine BC compat (is_array() call).
        $this->count = $count ? (int) (\is_array($count) ? $count['value'] : $count) : 3;
    }

    public function getRetryCount(): string
    {
        return $this->count;
    }
}
