<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class UuidHelper
{
    public static function normalize($value): ?UuidInterface
    {
        if (null === $value) {
            return $value;
        }
        if ($value instanceof UuidInterface) {
            return $value;
        }
        if (!\is_string($value) && !(\is_object($value) && \method_exists($value, '__toString'))) {
            throw new \Exception(\sprintf("Value must be a valid UUID string, \Stringable or '%s' instance", UuidInterface::class));
        }

        return Uuid::fromString((string) $value);
    }
}
