<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

#[\MakinaCorpus\CoreBus\Attr\CommandHandler]
class MockHandlerWithAttribute extends MockHandlerParent
{
    public static function nonEligibleStaticMethod(\DateTime $object): void
    {
    }

    protected function nonEligibleProtectedMethod(\DateTime $object): void
    {
    }

    private function nonEligiblePrivateMethod(\DateTime $object): void
    {
    }

    public function nonEligibleBuiltinParameterMethod(int $object): void
    {
    }

    public function nonEligibleUnspecifiedParameterMethod($object): void
    {
    }

    public function eligibleClassParameterMethod(\DateTime $object): void
    {
    }

    public function eligibleInterfaceParameterMethod(\DateTimeInterface $object): void
    {
    }

    public function nonEligibleMultipleParameterMethod(\DateTime $object, \DateTime $other): void
    {
    }
}
