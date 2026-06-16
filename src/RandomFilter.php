<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements randomly based on probability.
 */
final class RandomFilter extends AbstractFilter
{
    public function __construct(
        private readonly float $probability = 0.5,
        iterable $iterable = []
    ) {
        if ($probability < 0.0 || $probability > 1.0) {
            throw new \InvalidArgumentException('Probability must be between 0 and 1');
        }

        parent::__construct($iterable);
    }

    public function withProbability(float $probability): static
    {
        return new self($probability, $this->iterable);
    }

    public function getProbability(): float
    {
        return $this->probability;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return (mt_rand() / mt_getrandmax()) < $this->probability;
    }
}
