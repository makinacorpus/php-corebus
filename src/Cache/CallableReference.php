<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache;

final class CallableReference
{
    public string $className;
    public string $parameterName;
    public string $methodName;
    public string $serviceId;
    public bool $requiresResolve;

    public function __construct(string $className, string $parameterName, string $methodName, string $serviceId, bool $requiresResolve)
    {
        $this->className = $className;
        $this->parameterName = $parameterName;
        $this->methodName = $methodName;
        $this->serviceId = $serviceId;
        $this->requiresResolve = $requiresResolve;
    }

    public function __toString()
    {
        if ($this->serviceId) {
            return \sprintf("%s[%s]::%s(%s)", $this->serviceId, $this->className, $this->methodName, $this->parameterName);
        }
        return \sprintf("%s::%s(%s)", $this->className, $this->methodName, $this->parameterName);
    }
}
