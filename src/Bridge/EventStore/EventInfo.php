<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

use MakinaCorpus\Message\Property;
use Ramsey\Uuid\UuidInterface;

/**
 * @deprecated
 * @codeCoverageIgnore
 */
final class EventInfo
{
    private ?string $aggregateType = null;
    private ?UuidInterface $aggregateId = null;
    private ?UuidInterface $aggregateRoot = null;
    /** @var array<string,string> */
    private array $properties = [];

    /** @return $this */
    public function withAggregateType(?string $aggregateType): self
    {
        $this->aggregateType = $aggregateType;

        return $this;
    }

    /** @return $this */
    public function withAggregateId(?UuidInterface $aggregateId): self
    {
        $this->aggregateId = $aggregateId;

        return $this;
    }

    /** @return $this */
    public function withAggregateRoot(?UuidInterface $aggregateRoot): self
    {
        $this->aggregateRoot = $aggregateRoot;

        return $this;
    }

    /** @return $this */
    public function withProperty(string $name, ?string $value): self
    {
        if (null === $value) {
            unset($this->properties[$name]);
        } else {
            $this->properties[$name] = $value;
        }

        return $this;
    }

    /** @return $this */
    public function withUserId(?string $userId): self
    {
        $this->withProperty(Property::USER_ID, $userId);

        return $this;
    }

    public function getAggregateType(): ?string
    {
        return $this->aggregateType;
    }

    public function getAggregateId(): ?UuidInterface
    {
        return $this->aggregateId;
    }

    public function getAggregateRoot(): ?UuidInterface
    {
        return $this->aggregateRoot;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
