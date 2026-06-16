<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value contains the specified substring.
 */
final class ContainsFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $substring,
        private readonly bool $caseSensitive = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withSubstring(string $substring): static
    {
        return new self($substring, $this->caseSensitive, $this->iterable);
    }

    public function withCaseSensitive(bool $caseSensitive): static
    {
        return new self($this->substring, $caseSensitive, $this->iterable);
    }

    public function getSubstring(): string
    {
        return $this->substring;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $stringValue = (string) $value;

        if ($this->caseSensitive) {
            return str_contains($stringValue, $this->substring);
        }

        return stripos($stringValue, $this->substring) !== false;
    }
}
