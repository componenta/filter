<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts a percentage of elements from the beginning.
 * 
 * Example: PercentageFilter(30) on 10 elements will accept first 3 elements.
 * 
 * Note: This differs from the original Componenta implementation where the parameter
 * meant "percentage to filter OUT". Here it means "percentage to KEEP".
 * 
 * This filter is stateful and requires knowing the total count.
 */
final class PercentageFilter extends AbstractFilter
{
    private ?int $allowedCount = null;
    private int $counter = 0;

    public function __construct(
        private readonly float $percentage,
        iterable $iterable = []
    ) {
        if ($percentage < 0.0 || $percentage > 100.0) {
            throw new \InvalidArgumentException('Percentage must be between 0 and 100');
        }

        parent::__construct($iterable);
    }

    public function withPercentage(float $percentage): static
    {
        return new self($percentage, $this->iterable);
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if ($this->allowedCount === null) {
            return false;
        }

        if ($this->counter < $this->allowedCount) {
            $this->counter++;
            return true;
        }

        return false;
    }

    public function getIterator(): \Generator
    {
        // Convert to array if needed to count elements
        $data = is_array($this->iterable)
            ? $this->iterable
            : iterator_to_array($this->iterable);

        $total = count($data);
        $this->allowedCount = (int) floor($total * ($this->percentage / 100));
        $this->counter = 0;

        foreach ($data as $key => $value) {
            if ($this->accept($value, $key)) {
                yield $key => $value;
            }
        }
    }

    public function __clone(): void
    {
        $this->allowedCount = null;
        $this->counter = 0;
    }
}
