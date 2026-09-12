<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('checks huge exact multiples without pathological factorization time', function (): void {
    $base = 1_000_000_000;
    $chunks = [1];

    for ($i = 0; $i < 20_000; $i++) {
        $carry = 0;
        $count = count($chunks);

        for ($j = 0; $j < $count; $j++) {
            $value = ($chunks[$j] * 2) + $carry;

            if ($value >= $base) {
                $chunks[$j] = $value - $base;
                $carry = 1;
            } else {
                $chunks[$j] = $value;
                $carry = 0;
            }
        }

        if ($carry !== 0) {
            $chunks[] = $carry;
        }
    }

    $number = (string) array_pop($chunks);

    while ($chunks !== []) {
        $number .= str_pad((string) array_pop($chunks), 9, '0', STR_PAD_LEFT);
    }

    expect(strlen($number))->toBe(6_021);

    set_time_limit(1);

    try {
        expect((new MultipleOfFilter(2))->accept($number))->toBeTrue();
    } finally {
        set_time_limit(0);
    }
})->group('performance');
