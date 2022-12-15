<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Mock;

use MakinaCorpus\CoreBus\EventBus\EventListener;

#[\MakinaCorpus\CoreBus\Attr\EventListener]
final class MockEventParentClassListener implements EventListener
{
    public function listenToParentClass(MockEventParentClass $event): void
    {
        if ($event instanceof MockEventA) {
            ++$event->count;
        } else if ($event instanceof MockEventB) {
            ++$event->count;
        } else {
            throw new \Exception("This should not happen.");
        }
    }
}
