<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Mock;

use MakinaCorpus\CoreBus\EventBus\EventBus;

final class MockEventBus implements EventBus
{
    public array $events = [];

    public function notifyEvent(object $event): void
    {
        $this->events[] = $event;
    }
}
