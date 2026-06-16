<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string length is at least the minimum.
 */
final class MinLengthFilter extends AbstractFilter
{
    public function __construct(
        private readonly int $minLength,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withMinLength(int $minLength): static
    {
        return new self($minLength, $this->iterable);
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return strlen((string) $value) >= $this->minLength;
    }
}
