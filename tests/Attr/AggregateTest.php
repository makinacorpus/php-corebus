<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Attr;

use MakinaCorpus\CoreBus\Attr\Aggregate;
use PHPUnit\Framework\TestCase;

class AggregateTest extends TestCase
{
    public function testCanReadPublicProperty(): void
    {
        $aggregate = new Aggregate('publicProp');

        self::assertSame(12, $aggregate->findAggregateId(new AggregateTestMock()));
    }

    public function testCanReadPrivateProperty(): void
    {
        $aggregate = new Aggregate('privateProp');

        self::assertSame(13, $aggregate->findAggregateId(new AggregateTestMock()));
    }

    public function testCanReadStdClassProperty(): void
    {
        $aggregate = new Aggregate('arbitraryProp');

        $instance = new \stdClass();
        $instance->arbitraryProp = 11;

        self::assertSame(11, $aggregate->findAggregateId($instance));
    }

    public function testCanReadPublicMethod(): void
    {
        $aggregate = new Aggregate('publicMethod');

        self::assertSame(14, $aggregate->findAggregateId(new AggregateTestMock()));
    }

    public function testCanReadPrivateMethod(): void
    {
        $aggregate = new Aggregate('privateMethod');

        self::assertSame(15, $aggregate->findAggregateId(new AggregateTestMock()));
    }

    public function testCanReadPublicMethodWithOptionalParameters(): void
    {
        $aggregate = new Aggregate('publicMethodWithOptionalParam');

        self::assertSame(16, $aggregate->findAggregateId(new AggregateTestMock()));
    }

    public function testCanNotReadMethodWithNonOptionalParameters(): void
    {
        $aggregate = new Aggregate('publicMethodWithNonOptionalParam');

        self::assertNull($aggregate->findAggregateId(new AggregateTestMock()));
    }

    public function testGetters(): void
    {
        $instance1 = new Aggregate('test1', 'test2');
        self::assertSame('test1', $instance1->getAggregateIdPropertyName());
        self::assertSame('test2', $instance1->getAggregateType());

        $instance1 = new Aggregate('test3');
        self::assertSame('test3', $instance1->getAggregateIdPropertyName());
        self::assertNull($instance1->getAggregateType());
    }
}
