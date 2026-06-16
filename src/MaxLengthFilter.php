<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string length is at most the maximum.
 */
final class MaxLengthFilter extends AbstractFilter
{
    public function __construct(
        private readonly int $maxLength,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withMaxLength(int $maxLength): static
    {
        return new self($maxLength, $this->iterable);
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return strlen((string) $value) <= $this->maxLength;
    }
}
