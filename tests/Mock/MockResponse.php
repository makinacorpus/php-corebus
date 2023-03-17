<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Mock;

final class MockResponse
{
    public object $command;

    public function __construct(object $command)
    {
        $this->command = $command;
    }
}
