<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Mock;

#[\MakinaCorpus\CoreBus\Attr\NoTransaction]
final class MockCommandNoTransaction
{
    public bool $done = false;
}
