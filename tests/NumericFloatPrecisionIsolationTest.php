<?php

declare(strict_types=1);

use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\RangeFilter;

it('keeps float comparisons independent from serialize_precision', function (): void {
    $previous = ini_get('serialize_precision');

    expect(ini_set('serialize_precision', '3'))->not->toBeFalse();

    try {
        expect((new GreaterThanFilter(1.233))->accept(1.234))->toBeTrue()
            ->and((new RangeFilter(1.234, 1.234))->accept(1.233))->toBeFalse()
            ->and(ini_get('serialize_precision'))->toBe('3');
    } finally {
        ini_set('serialize_precision', (string) $previous);
    }
});
