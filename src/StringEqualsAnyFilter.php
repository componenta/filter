<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value equals any of the allowed strings.
 */
final class StringEqualsAnyFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $allowed,
        private readonly bool $caseSensitive = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withAllowed(array $allowed): static
    {
        return new self($allowed, $this->caseSensitive, $this->iterable);
    }

    public function withCaseSensitive(bool $caseSensitive): static
    {
        return new self($this->allowed, $caseSensitive, $this->iterable);
    }

    public function getAllowed(): array
    {
        return $this->allowed;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $stringValue = (string) $value;

        if ($this->caseSensitive) {
            return in_array($stringValue, $this->allowed, true);
        }

        foreach ($this->allowed as $allowed) {
            if (strcasecmp($stringValue, (string) $allowed) === 0) {
                return true;
            }
        }

        return false;
    }
}
