<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache;

final class CallableReference
{
    public string $className;
    public string $methodName;
    public string $serviceId;

    public function __construct(string $commandClassName, string $methodName, string $serviceId)
    {
        $this->className = $commandClassName;
        $this->methodName = $methodName;
        $this->serviceId = $serviceId;
    }

    public function __toString()
    {
        if ($this->serviceId) {
            return \sprintf("%s[%s]::%s()", $this->serviceId, $this->className, $this->methodName);
        }
        return \sprintf("%s::%s()", $this->className, $this->methodName);
    }
}
