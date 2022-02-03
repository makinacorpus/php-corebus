<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Mock;

use MakinaCorpus\CoreBus\EventBus\EventListener;

final class MockEventListener implements EventListener
{
    /**
     * Cannot use when more than one parameter.
     *
     * @codeCoverageIgnore
     */
    public function doNotA(MockEventA $event, int $foo): void
    {
        throw new \BadMethodCallException("I shall not be called.");
    }

    /**
     * OK.
     */
    public function doA(MockEventA $event): void
    {
        ++$event->count;
    }

    /**
     * OK.
     */
    public function doAAnotherOne(MockEventA $event): void
    {
        ++$event->count;
    }

    /**
     * Cannot use when no or wrong type hinting.
     *
     * @codeCoverageIgnore
     */
    public function doNotB(MockCommandB $event): void
    {
        throw new \BadMethodCallException("I shall not be called.");
    }

    /**
     * OK.
     */
    public function doB(MockEventB $event): void
    {
        ++$event->count;
    }
}
