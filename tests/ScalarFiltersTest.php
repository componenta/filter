<?php

declare(strict_types=1);

use Componenta\Filter\BetweenFilter;
use Componenta\Filter\CallbackFilter;
use Componenta\Filter\StringFilter;

it('accepts strings and Stringable values', function (): void {
    $filter = new StringFilter();

    $stringable = new class implements Stringable {
        public function __toString(): string
        {
            return 'value';
        }
    };

    expect($filter->accept('value'))->toBeTrue()
        ->and($filter->accept($stringable))->toBeTrue()
        ->and($filter->accept(10))->toBeFalse();
});

it('supports inclusive and exclusive numeric bounds', function (): void {
    $inclusive = new BetweenFilter(1, 3);
    $exclusive = new BetweenFilter(1, 3, inclusive: false);

    expect($inclusive->accept(1))->toBeTrue()
        ->and($inclusive->accept(2))->toBeTrue()
        ->and($inclusive->accept(3))->toBeTrue()
        ->and($exclusive->accept(1))->toBeFalse()
        ->and($exclusive->accept(2))->toBeTrue()
        ->and($exclusive->accept(3))->toBeFalse()
        ->and($inclusive->accept('not numeric'))->toBeFalse();
});

it('passes both value and key to callbacks', function (): void {
    $filter = new CallbackFilter(
        static fn(mixed $value, string|int|null $key): bool => $key === 'enabled' && $value === true,
    );

    expect($filter->accept(true, 'enabled'))->toBeTrue()
        ->and($filter->accept(true, 'disabled'))->toBeFalse();
});
