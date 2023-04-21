<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

/**
 * Union types are unsupported yet.
 */
class MockHandlerErrorUnsupportedUnionTypeNamed
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: 'some')]
    public function error(\DateTime|Request $some): void
    {
    }
}
