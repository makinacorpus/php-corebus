<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Type;

class MockHandler extends MockHandlerParent
{
    public function nonEligibleUnattributedMethod(\DateTime $object): void
    {
    }

    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    public function eligibleUnspecifiedParameterMethod($object): void
    {
    }

    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTimeImmutable::class)]
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTimeInterface::class)]
    public function eligibleMultipleUserTypeMethod(\DateTimeInterface $object): void
    {
    }
}
