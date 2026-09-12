<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Keeps a percentage of elements from the beginning of an iterable.
 *
 * This is a collection operator, not a predicate: acceptance depends on the
 * size and position of the complete iterable.
 */
final class PercentageFilter extends AbstractCollectionFilter
{
    public function __construct(
        private readonly float $percentage,
        iterable $iterable = []
    ) {
        if (!is_finite($percentage) || $percentage < 0.0 || $percentage > 100.0) {
            throw new \InvalidArgumentException('Percentage must be a finite number between 0 and 100');
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

    public function getIterator(): \Generator
    {
        if ($this->percentage === 0.0) {
            return;
        }

        if ($this->percentage === 100.0) {
            yield from $this->iterable;

            return;
        }

        if (is_array($this->iterable) || $this->iterable instanceof \Countable) {
            $allowedCount = (int) floor(count($this->iterable) * ($this->percentage / 100));

            if ($allowedCount === 0) {
                return;
            }

            $yielded = 0;

            foreach ($this->iterable as $key => $value) {
                yield $key => $value;

                if (++$yielded >= $allowedCount) {
                    break;
                }
            }

            return;
        }

        $items = [];

        foreach ($this->iterable as $key => $value) {
            $items[] = [$key, $value];
        }

        $allowedCount = (int) floor(count($items) * ($this->percentage / 100));

        for ($i = 0; $i < $allowedCount; $i++) {
            [$key, $value] = $items[$i];
            yield $key => $value;
        }
    }
}
