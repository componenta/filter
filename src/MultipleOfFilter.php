<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose numeric value is a multiple of the divisor.
 */
final class MultipleOfFilter extends AbstractFilter
{
    public function __construct(
        private readonly float $divisor,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withDivisor(float $divisor): static
    {
        return new self($divisor, $this->iterable);
    }

    public function getDivisor(): float
    {
        return $this->divisor;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_numeric($value) || $this->divisor == 0.0) {
            return false;
        }

        $num = (float) $value;
        return fmod($num, $this->divisor) === 0.0;
    }
}
