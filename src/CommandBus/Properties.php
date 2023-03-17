<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

/**
 * Message properties container.
 */
class Properties
{
    private array $data = [];

    /**
     * Override properties.
     *
     * @return $this
     */
    public function __construct(?array $properties = null)
    {
        if ($properties) {
            $this->setProperties($properties);
        }
    }

    /**
     * Validate and set properties.
     */
    protected function setProperties(array $properties): void
    {
        foreach ($properties as $key => $value) {
            if (null === $value || '' === $value) {
                unset($this->data[$key]);
            } else if (\is_scalar($value) || $value instanceof \Stringable) {
                $this->data[$key] = (string) $value;
            } else {
                throw new \InvalidArgumentException(\sprintf("Property value for key '%s' must be a string or scalar, '%s' given.", $key, \get_debug_type($value)));
            }
        }
    }

    /**
     * Create new instance with additional properties.
     *
     * Null or empty values will remove values from the resulting object if
     * set in current object.
     *
     * @return static
     */
    public function cloneWith(array $properties = null): self
    {
        $ret = clone $this;
        $ret->setProperties($properties);

        return $ret;
    }

    /**
     * Get message identifier computed from properties.
     */
    public function getMessageId(): ?string
    {
        return $this->get('message-id');
    }

    /**
     * Get reply to value.
     */
    public function getReplyTo(): ?string
    {
        return $this->get('reply-to');
    }

    /**
     * Get the content encoding property.
     */
    public function getMessageContentEncoding(): ?string
    {
        return $this->get('content-encoding', 'UTF-8');
    }

    /**
     * Get the content type property.
     */
    public function getMessageContentType(): ?string
    {
        return $this->get('content-type', 'application/json');
    }

    /**
     * Get the subject property.
     */
    public function getMessageSubject(): ?string
    {
        return $this->get('subject');
    }

    /**
     * Get the user identifier property.
     */
    public function getMessageUserId(): ?string
    {
        return $this->get('user_id');
    }

    /**
     * Get property value.
     */
    public function get(string $name, ?string $default = null): ?string
    {
        return $this->data[$name] ?? $default;
    }

    /**
     * Does the given property is set.
     */
    public function has(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Get properties.
     */
    public function all(): array
    {
        return $this->data;
    }
}
