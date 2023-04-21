<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Mock;

use MakinaCorpus\CoreBus\CommandBus\CommandHandler;

#[\MakinaCorpus\CoreBus\Attr\CommandHandler]
final class MockHandler implements CommandHandler
{
    /**
     * No parameter with type.
     *
     * @codeCoverageIgnore
     */
    public function doNotA($command, int $foo): void
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
