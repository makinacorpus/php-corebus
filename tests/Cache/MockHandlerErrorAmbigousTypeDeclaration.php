<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

/**
 * Method has more than one parameter, all are compatible with the user
 * given command type: definition is ambiguous because we don't know which
 * one is the command.
 */
class MockHandlerErrorAmbigousTypeDeclaration
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTimeInterface::class)]
    public function error(\DateTime $date1, \DateTimeImmutable $date2): void
    {
    }
}
