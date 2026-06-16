<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value ends with the specified suffix.
 */
final class EndsWithFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $suffix,
        private readonly bool $caseSensitive = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withSuffix(string $suffix): static
    {
        return new self($suffix, $this->caseSensitive, $this->iterable);
    }

    public function withCaseSensitive(bool $caseSensitive): static
    {
        return new self($this->suffix, $caseSensitive, $this->iterable);
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $stringValue = (string) $value;
        $suffixLen = strlen($this->suffix);

        if ($suffixLen === 0) {
            return true;
        }

        if ($this->caseSensitive) {
            return str_ends_with($stringValue, $this->suffix);
        }

        return strcasecmp(substr($stringValue, -$suffixLen), $this->suffix) === 0;
    }
}
