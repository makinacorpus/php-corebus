<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Attribute\Loader;

/**
 * @codeCoverageIgnore
 */
#[\MakinaCorpus\CoreBus\Attr\Aggregate("foo", AttributeLoaderMock::class)]
#[\MakinaCorpus\CoreBus\Attr\Async]
#[\MakinaCorpus\CoreBus\Attr\Retry(10)]
abstract class AttributeLoaderMock
{
    #[\MakinaCorpus\CoreBus\Attr\Aggregate("foo", AttributeLoaderMock::class)]
    #[\MakinaCorpus\CoreBus\Attr\Async]
    #[\MakinaCorpus\CoreBus\Attr\Retry(10)]
    public function normalMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
    }

    abstract public function abstractMethod(): void;
}

/**
 * @codeCoverageIgnore
 */
#[\MakinaCorpus\CoreBus\Attr\Aggregate("foo", AttributeLoaderMock::class)]
#[\MakinaCorpus\CoreBus\Attr\Async]
#[\MakinaCorpus\CoreBus\Attr\Retry(10)]
function readPoliciesFromMe(): void
{
}
