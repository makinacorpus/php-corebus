<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

class MockHandlerErrorNoType
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler]
    public function error($object): void
    {
    }
}
