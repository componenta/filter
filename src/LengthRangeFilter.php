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
        if ($minLength < 0 || $minLength > $maxLength) {
            throw new \InvalidArgumentException('Lengths must be non-negative and minLength must not exceed maxLength');
        }

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
        $stringValue = StringValue::from($value);

        if ($stringValue === null) {
            return false;
        }

        $length = strlen($stringValue);

        return $length >= $this->minLength && $length <= $this->maxLength;
    }
}
