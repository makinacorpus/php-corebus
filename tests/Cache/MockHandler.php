<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

use Symfony\Component\HttpFoundation\Request;

class MockHandler extends MockHandlerParent
{
    /**
     * There is only one, with one type, use this.
     */
    public function nonEligibleUnattributedMethod(\DateTime $object): void
    {
    }

    /**
     * Using a parameter name, which has a type, use it.
     */
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: 'object')]
    public function eligibleWithNamedParameter(\DateTime $object, Request $request): void
    {
    }

    /**
     * Using a parameter type, with no other compatible parameters, use it.
     */
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    public function eligibleWithServiceArgumentInjection(\DateTime $object, Request $request): void
    {
    }

    /**
     * Multiple command handlers for multiple classes, all work fine.
     */
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTimeImmutable::class)]
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTimeInterface::class)]
    public function eligibleMultipleUserTypeMethod(\DateTimeInterface $object): void
    {
    }
}
