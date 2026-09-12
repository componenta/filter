<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('checks huge coprime exact divisors without quadratic decimal long division', function (): void {
    $digits = 3_000;
    $nonMultipleDivisor = '7' . str_repeat('3', $digits - 1);
    $nonMultipleValue = '8' . str_repeat('7', ($digits * 2) - 1);
    $exactDivisor = str_repeat('9', $digits);
    $exactValue = str_repeat('9', $digits * 2);

    set_time_limit(1);

    try {
        expect((new MultipleOfFilter($nonMultipleDivisor))->accept($nonMultipleValue))->toBeFalse()
            ->and((new MultipleOfFilter($exactDivisor))->accept($exactValue))->toBeTrue();
    } finally {
        set_time_limit(0);
    }
})->group('performance');
