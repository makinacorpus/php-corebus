<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Mock;

final class MockEventC implements MockEventInterface
{
    public int $count = 0;
}
