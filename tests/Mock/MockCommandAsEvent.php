<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Mock;

#[\MakinaCorpus\CoreBus\Attr\CommandAsEvent]
final class MockCommandAsEvent
{
    public bool $done = false;
}
