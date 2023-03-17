<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache;

/**
 * Allow runtime lookup of handlers.
 *
 * This is a last resort implementation, and is configured by default if you
 * are using this library via a standalone misconfigured setup.
 *
 * It works, but it is slow, you are strongly advised to setup a cache warm up
 * phase that will allow to skip this at runtime.
 */
final class RuntimeCallableReferenceList implements CallableReferenceList
{
    private bool $allowMultiple;
    /** @var array<string,CallableReference[]> */
    private array $references = [];

    public function __construct(bool $allowMultiple)
    {
        $this->allowMultiple = $allowMultiple;
    }

    /**
     * Parse class and append references into this list.
     */
    public function appendFromClass(string $handlerClassName, ?string $handlerServiceId = null): void
    {
        $classParser = new ClassParser();
        foreach ($classParser->lookup($handlerClassName) as $reference) {
            $this->append($reference, $handlerServiceId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function first(string $className): ?CallableReference
    {
        return $this->references[$className][0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function all(string $className): iterable
    {
        return $this->references[$className] ?? [];
    }

    /**
     * Prepare, validate and append given reference.
     */
    private function append(CallableReference $reference, ?string $handlerServiceId = null): void
    {
        if ($handlerServiceId) {
            $reference->serviceId = $handlerServiceId;
        }

        $existing = $this->references[$reference->className][0] ?? null;

        if ($existing && !$this->allowMultiple) {
            \assert($existing instanceof CallableReference);

            throw new \LogicException(\sprintf(
                "Handler for command class %s is already defined using %s::%s, found %s::%s",
                $reference->className,
                $existing->serviceId,
                $existing->methodName,
                $reference->serviceId,
                $reference->methodName
            ));
        }

        $this->references[$reference->className][] = $reference;
    }
}
