<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value equals the expected string.
 */
final class StringEqualsFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $expected,
        private readonly bool $caseSensitive = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withExpected(string $expected): static
    {
        return new self($expected, $this->caseSensitive, $this->iterable);
    }

    public function withCaseSensitive(bool $caseSensitive): static
    {
        return new self($this->expected, $caseSensitive, $this->iterable);
    }

    public function getExpected(): string
    {
        return $this->expected;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $stringValue = (string) $value;

        return $this->caseSensitive
            ? $stringValue === $this->expected
            : strcasecmp($stringValue, $this->expected) === 0;
    }
}
