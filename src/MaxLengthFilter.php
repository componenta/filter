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
        if ($maxLength < 0) {
            throw new \InvalidArgumentException('Maximum length must be non-negative');
        }

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
        $stringValue = StringValue::from($value);

        return $stringValue !== null && strlen($stringValue) <= $this->maxLength;
    }
}
