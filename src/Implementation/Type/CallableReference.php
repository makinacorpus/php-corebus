<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Implementation\Type;

final class CallableReference
{
    public string $className;
    public string $methodName;
    public string $serviceId;

    public function __construct(string $className, string $methodName, ?string $serviceId)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->serviceId = $serviceId ?? $className;
    }
}
