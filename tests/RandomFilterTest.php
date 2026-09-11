<?php

declare(strict_types=1);

use Componenta\Filter\RandomFilter;

it('rejects NaN probability', function (): void {
    new RandomFilter(NAN);
})->throws(InvalidArgumentException::class);

it('has deterministic behavior at probability boundaries', function (): void {
    $never = new RandomFilter(0.0);
    $always = new RandomFilter(1.0);

    foreach (range(1, 100) as $value) {
        expect($never->accept($value))->toBeFalse()
            ->and($always->accept($value))->toBeTrue();
    }
});
