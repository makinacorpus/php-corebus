<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * @codeCoverageIgnore
 */
trait SynchronousCommandBusAwareTrait /* implements SynchronousCommandBusAware */
{
    private ?SynchronousCommandBus $synchronousCommandBus = null;

    /**
     * {@inheritdoc}
     */
    public function setSynchronousCommandBus(CommandBus $synchronousCommandBus): void
    {
        $this->synchronousCommandBus = $synchronousCommandBus;
    }

    protected function getSynchronousCommandBus(): SynchronousCommandBus
    {
        if (!$this->synchronousCommandBus) {
            throw new \LogicException("Synchronous bus was not set.");
        }

        return $this->synchronousCommandBus;
    }
}
