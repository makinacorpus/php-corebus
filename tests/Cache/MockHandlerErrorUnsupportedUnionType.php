<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

/**
 * Union types are unsupported yet.
 */
class MockHandlerErrorUnsupportedUnionType
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler()]
    public function error(\DateTime|Request $some): void
    {
    }
}
