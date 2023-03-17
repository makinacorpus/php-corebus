<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Mock;

use MakinaCorpus\CoreBus\EventBus\EventListener;

#[\MakinaCorpus\CoreBus\Attr\EventListener]
final class MockEventInterfaceListener implements EventListener
{
    public function listenToInterface(MockEventInterface $event): void
    {
        if ($event instanceof MockEventA) {
            ++$event->count;
        } else if ($event instanceof MockEventC) {
            ++$event->count;
        } else {
            throw new \Exception("This should not happen.");
        }
    }
}
