<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value starts with the specified prefix.
 */
final class StartsWithFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $prefix,
        private readonly bool $caseSensitive = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withPrefix(string $prefix): static
    {
        return new self($prefix, $this->caseSensitive, $this->iterable);
    }

    public function withCaseSensitive(bool $caseSensitive): static
    {
        return new self($this->prefix, $caseSensitive, $this->iterable);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $stringValue = (string) $value;
        $prefixLen = strlen($this->prefix);

        if ($prefixLen === 0) {
            return true;
        }

        if ($this->caseSensitive) {
            return str_starts_with($stringValue, $this->prefix);
        }

        return strncasecmp($stringValue, $this->prefix, $prefixLen) === 0;
    }
}
