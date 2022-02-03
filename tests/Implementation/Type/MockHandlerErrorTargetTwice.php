<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Type;

class MockHandlerErrorTargetTwice
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    public function error(\DateTime $object): void
    {
    }
}
