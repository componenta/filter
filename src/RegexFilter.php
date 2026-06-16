<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value matches the regex pattern.
 */
final class RegexFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $pattern,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withPattern(string $pattern): static
    {
        return new self($pattern, $this->iterable);
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return preg_match($this->pattern, (string) $value) === 1;
    }
}
