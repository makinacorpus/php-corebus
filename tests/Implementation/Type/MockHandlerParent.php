<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Type;

class MockHandlerParent
{
    public function nonEligibleParentClassMethod(\DateTime $object): void
    {
    }

    #[\MakinaCorpus\CoreBus\Attr\CommandHandler]
    public function nonEligibleParentClassWithAttributeMethod(\DateTime $object): void
    {
    }
}
