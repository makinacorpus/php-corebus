<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

class MockHandlerErrorMoreThanOneParameter
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    public function error(\DateTime $object, \DateTime $other): void
    {
    }
}
