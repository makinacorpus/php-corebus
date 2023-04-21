<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Mock;

use MakinaCorpus\CoreBus\EventBus\EventListener;

#[\MakinaCorpus\CoreBus\Attr\EventListener]
final class MockEventListener implements EventListener
{
    /**
     * OK.
     */
    public function doNotA(MockEventA $event, ?int $foo = null): void
    {
        ++$event->count;
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
