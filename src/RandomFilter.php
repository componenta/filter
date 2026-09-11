<?php

declare(strict_types=1);

namespace Componenta\Filter;

use Random\Randomizer;

/**
 * Accepts elements randomly based on probability.
 *
 * Uses an isolated Randomizer by default and never mutates PHP's global
 * mt_rand() state. A Randomizer may be injected for reproducible behavior.
 */
final class RandomFilter extends AbstractFilter
{
    private readonly Randomizer $randomizer;

    public function __construct(
        private readonly float $probability = 0.5,
        iterable $iterable = [],
        ?Randomizer $randomizer = null,
    ) {
        if (!is_finite($probability) || $probability < 0.0 || $probability > 1.0) {
            throw new \InvalidArgumentException('Probability must be a finite number between 0 and 1');
        }

        $this->randomizer = $randomizer ?? new Randomizer();
        parent::__construct($iterable);
    }

    public function withProbability(float $probability): static
    {
        return new self($probability, $this->iterable, $this->randomizer);
    }

    public function withRandomizer(Randomizer $randomizer): static
    {
        return new self($this->probability, $this->iterable, $randomizer);
    }

    public function getProbability(): float
    {
        return $this->probability;
    }

    public function getRandomizer(): Randomizer
    {
        return $this->randomizer;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if ($this->probability === 0.0) {
            return false;
        }

        if ($this->probability === 1.0) {
            return true;
        }

        return $this->randomizer->nextFloat() < $this->probability;
    }
}
