<?php

declare(strict_types=1);

use Componenta\Filter\RandomFilter;
use Random\Engine;
use Random\Engine\Mt19937;
use Random\Randomizer;

dataset('invalid probabilities', [
    'below zero' => -0.1,
    'above one' => 1.1,
    'NaN' => NAN,
    'positive infinity' => INF,
    'negative infinity' => -INF,
]);

it('rejects invalid probability configuration', function (float $probability): void {
    new RandomFilter($probability);
})->with('invalid probabilities')->throws(InvalidArgumentException::class);

it('has deterministic behavior at probability boundaries', function (): void {
    $never = new RandomFilter(0.0);
    $always = new RandomFilter(1.0);

    foreach (range(1, 100) as $value) {
        expect($never->accept($value))->toBeFalse()
            ->and($always->accept($value))->toBeTrue();
    }
});

it('does not consume RNG state at deterministic probability boundaries', function (float $probability, bool $expected): void {
    $seed = 123456;
    $randomizer = new Randomizer(new Mt19937($seed));
    $control = new Randomizer(new Mt19937($seed));
    $filter = new RandomFilter($probability, randomizer: $randomizer);

    expect($filter->accept('value'))->toBe($expected)
        ->and($filter->getRandomizer()->nextFloat())->toBe($control->nextFloat());
})->with([
    'never' => [0.0, false],
    'always' => [1.0, true],
]);

it('uses a strict probability boundary', function (): void {
    $seed = 42;
    $control = new Randomizer(new Mt19937($seed));
    $probability = $control->nextFloat();
    $filter = new RandomFilter(
        $probability,
        randomizer: new Randomizer(new Mt19937($seed)),
    );

    expect($filter->accept('exact boundary'))->toBeFalse();
});

it('honors iterable binding at deterministic probability boundaries', function (): void {
    expect((new RandomFilter(0.0, [1, 2, 3]))->toArray())->toBe([])
        ->and((new RandomFilter(1.0, [1, 2, 3]))->toArray())->toBe([1, 2, 3]);
});

it('does not mutate the process-global mt_rand state', function (): void {
    mt_srand(123456);
    $expectedFirst = mt_rand();
    $expectedSecond = mt_rand();

    mt_srand(123456);
    $actualFirst = mt_rand();
    (new RandomFilter(0.5))->accept('value');
    $actualSecond = mt_rand();

    expect($actualFirst)->toBe($expectedFirst)
        ->and($actualSecond)->toBe($expectedSecond);
});

it('supports an injected deterministic Randomizer', function (): void {
    $first = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937(42)),
    );
    $second = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937(42)),
    );

    $firstSequence = [];
    $secondSequence = [];

    foreach (range(1, 100) as $value) {
        $firstSequence[] = $first->accept($value);
        $secondSequence[] = $second->accept($value);
    }

    expect($firstSequence)->toBe($secondSequence)
        ->and(array_unique($firstSequence))->toHaveCount(2);
});

it('does not share RNG state with an immutable iterable clone', function (): void {
    $original = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937(2)),
    );
    $changed = $original->withIterable([1, 2, 3]);
    $control = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937(2)),
    );

    $changed->accept('consume clone RNG');

    expect($original->accept('original'))->toBe($control->accept('control'));
});

it('does not share RNG state with a probability clone', function (): void {
    $original = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937(2)),
    );
    $changed = $original->withProbability(0.5);
    $control = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937(2)),
    );

    $changed->accept('consume clone RNG');

    expect($original->accept('original'))->toBe($control->accept('control'));
});

it('copies the default secure engine for immutable clones', function (): void {
    $original = new RandomFilter(0.5);
    $iterableClone = $original->withIterable([1, 2, 3]);
    $probabilityClone = $original->withProbability(0.25);

    expect($iterableClone)->toBeInstanceOf(RandomFilter::class)
        ->and($probabilityClone)->toBeInstanceOf(RandomFilter::class)
        ->and($iterableClone->getRandomizer())->not->toBe($original->getRandomizer())
        ->and($probabilityClone->getRandomizer())->not->toBe($original->getRandomizer());
});

it('fails fast when an injected engine cannot be copied immutably', function (): void {
    $engine = new class implements Engine {
        private function __clone() {}

        public function generate(): string
        {
            return random_bytes(8);
        }
    };

    $filter = new RandomFilter(
        0.5,
        randomizer: new Randomizer($engine),
    );

    expect(fn() => $filter->withIterable([1, 2, 3]))
        ->toThrow(LogicException::class, 'cannot be copied immutably')
        ->and(fn() => $filter->withProbability(0.25))
        ->toThrow(LogicException::class, 'cannot be copied immutably');
});
