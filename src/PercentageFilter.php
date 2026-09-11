<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts a percentage of elements from the beginning of an iterable.
 *
 * The percentage applies to the collection as a whole, so this filter cannot be
 * evaluated meaningfully through accept() without collection context.
 */
final class PercentageFilter extends AbstractFilter
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

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        throw new \LogicException('PercentageFilter requires collection context; iterate over the filter instead');
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
            $index = 0;

            foreach ($this->iterable as $key => $value) {
                if ($index++ >= $allowedCount) {
                    break;
                }

                yield $key => $value;
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
