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
        if (@preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException(sprintf('Invalid regular expression: %s', $pattern));
        }

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
        $stringValue = StringValue::from($value);

        return $stringValue !== null && preg_match($this->pattern, $stringValue) === 1;
    }
}
