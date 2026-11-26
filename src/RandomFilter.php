<?php

declare(strict_types=1);

namespace Componenta\Filter;

use Random\Engine;
use Random\Engine\Secure;
use Random\Randomizer;

/**
 * Accepts elements randomly based on probability.
 *
 * Uses a private Secure Randomizer by default and never mutates PHP's global
 * mt_rand() state. An injected Randomizer is a borrowed, stateful collaborator.
 * Configuration copies use the engine's copying protocol. A custom engine must
 * implement independent clone/serialization semantics if independent sequences
 * are required; the filter cannot enforce isolation of external/static state.
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

    /**
     * Returns the live collaborator, not a snapshot. Drawing from it advances
     * the same random stream used by this filter.
     */
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

        try {
            if ($reflection->isInternal() || $reflection->hasMethod('__clone')) {
                return new Randomizer(clone $engine);
            }

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
