<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Mock;

use MakinaCorpus\CoreBus\CommandBus\CommandHandler;

final class MockHandler implements CommandHandler
{
    /**
     * Cannot use when more than one parameter.
     *
     * @codeCoverageIgnore
     */
    public function doNotA(MockCommandA $command, int $foo): void
    {
        throw new \BadMethodCallException("I shall not be called.");
    }

    /**
     * OK.
     */
    public function doA(MockCommandA $command): void
    {
        $command->done = true;
    }

    /**
     * Cannot use when no or wrong type hinting.
     *
     * @codeCoverageIgnore
     */
    public function doNotB(MockEventA $command): void
    {
        throw new \BadMethodCallException("I shall not be called.");
    }

    /**
     * OK.
     */
    public function doB(MockCommandB $command): void
    {
        $command->done = true;
    }
}
