<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

/**
 * Parameter has no type, we cannot route commands or events.
 */
class MockHandlerErrorNoType
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler]
    public function error($object): void
    {
    }
}
