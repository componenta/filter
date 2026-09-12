<?php

declare(strict_types=1);

use Componenta\Filter\RandomFilter;
use Random\Engine;
use Random\Randomizer;

final class RandomFilterCustomCloneState
{
    public int $counter = 0;
}

final class RandomFilterCustomCloneEngine implements Engine
{
    public RandomFilterCustomCloneState $state;

    public Closure $callback;

    public function __construct()
    {
        $this->state = new RandomFilterCustomCloneState();
        $this->callback = static fn(): null => null;
    }

    public function __clone(): void
    {
        $this->state = clone $this->state;
    }

    public function generate(): string
    {
        return $this->state->counter++ === 0
            ? str_repeat("\0", 8)
            : str_repeat("\xFF", 8);
    }
}

it('honors an explicit clone hook on a non-serializable custom engine', function (): void {
    $original = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new RandomFilterCustomCloneEngine()),
    );
    $changed = $original->withProbability(0.5);

    expect($changed->accept('consume clone RNG'))->toBeTrue()
        ->and($original->accept('original still starts at first sample'))->toBeTrue()
        ->and($changed->accept('clone advances independently'))->toBeFalse()
        ->and($original->accept('original advances independently'))->toBeFalse();
});
