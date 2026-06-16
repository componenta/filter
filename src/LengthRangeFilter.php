<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string length is within a range.
 */
final class LengthRangeFilter extends AbstractFilter
{
    public function __construct(
        private readonly int $minLength,
        private readonly int $maxLength,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withMinLength(int $minLength): static
    {
        return new self($minLength, $this->maxLength, $this->iterable);
    }

    public function withMaxLength(int $maxLength): static
    {
        return new self($this->minLength, $maxLength, $this->iterable);
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $length = strlen((string) $value);
        return $length >= $this->minLength && $length <= $this->maxLength;
    }
}
