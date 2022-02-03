<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Implementation\Type;

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

    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: \DateTime::class)]
    public function eligibleUnspecifiedParameterMethod($object): void
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
