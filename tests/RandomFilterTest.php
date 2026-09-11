<?php

declare(strict_types=1);

use Componenta\Filter\RandomFilter;

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

it('honors iterable binding at deterministic probability boundaries', function (): void {
    expect((new RandomFilter(0.0, [1, 2, 3]))->toArray())->toBe([])
        ->and((new RandomFilter(1.0, [1, 2, 3]))->toArray())->toBe([1, 2, 3]);
});
