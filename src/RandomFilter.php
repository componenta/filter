<?php

declare(strict_types=1);

namespace Componenta\Filter;

use Random\Engine;
use Random\Engine\Secure;
use Random\Randomizer;

/**
 * Accepts elements randomly based on probability.
 *
 * Uses an isolated Randomizer by default and never mutates PHP's global
 * mt_rand() state. A Randomizer may be injected for reproducible behavior.
 * Immutable clones preserve independent engine state instead of sharing it.
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

    public function __clone(): void
    {
        $this->randomizer = self::copyRandomizer($this->randomizer);
    }

    public function withProbability(float $probability): static
    {
        return new self(
            $probability,
            $this->iterable,
            self::copyRandomizer($this->randomizer),
        );
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

    private static function copyRandomizer(Randomizer $randomizer): Randomizer
    {
        $engine = $randomizer->engine;

        if ($engine instanceof Secure) {
            return new Randomizer(new Secure());
        }

        $reflection = new \ReflectionObject($engine);

        if (!$reflection->isCloneable()) {
            throw self::uncopyableEngine($engine);
        }

        if ($reflection->isInternal() || $reflection->hasMethod('__clone')) {
            return new Randomizer(clone $engine);
        }

        try {
            $copy = unserialize(
                serialize($engine),
                ['allowed_classes' => true],
            );
        } catch (\Throwable $exception) {
            throw self::uncopyableEngine($engine, $exception);
        }

        if (!$copy instanceof Engine) {
            throw self::uncopyableEngine($engine);
        }

        return new Randomizer($copy);
    }

    private static function uncopyableEngine(
        Engine $engine,
        ?\Throwable $previous = null,
    ): \LogicException {
        return new \LogicException(
            sprintf('Random engine %s cannot be copied immutably', $engine::class),
            previous: $previous,
        );
    }
}
