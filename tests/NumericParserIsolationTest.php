<?php

declare(strict_types=1);

use Componenta\Filter\EvenNumberFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\MultipleOfFilter;
use Componenta\Filter\NumericFilter;
use Componenta\Filter\OddNumberFilter;
use Componenta\Filter\RangeFilter;

it('does not depend on PCRE limits for numeric parsing on cold or hot paths', function (): void {
    $range = new RangeFilter('0.1', '100');
    $multiple = new MultipleOfFilter('0.125');
    $numeric = new NumericFilter();
    $backtrack = ini_get('pcre.backtrack_limit');
    $jit = ini_get('pcre.jit');
    $recursion = ini_get('pcre.recursion_limit');

    try {
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '1');
        ini_set('pcre.recursion_limit', '1');

        expect($range->accept('1.25'))->toBeTrue()
            ->and((new RangeFilter('0.1', '100'))->accept('1.25'))->toBeTrue()
            ->and((new GreaterThanFilter('1e1000'))->accept('2e1000'))->toBeTrue()
            ->and($multiple->accept('1.25'))->toBeTrue()
            ->and($multiple->accept('1.25'))->toBeTrue()
            ->and((new EvenNumberFilter())->accept('4.0e1'))->toBeTrue()
            ->and((new OddNumberFilter())->accept('3e0'))->toBeTrue()
            ->and($numeric->accept('1e' . str_repeat('9', 10000)))->toBeTrue()
            ->and($numeric->accept('1e+'))->toBeFalse()
            ->and($range->accept('bad'))->toBeFalse();
    } finally {
        ini_set('pcre.backtrack_limit', (string) $backtrack);
        ini_set('pcre.jit', (string) $jit);
        ini_set('pcre.recursion_limit', (string) $recursion);
    }
});

it('accepts the PHP decimal string grammar without interpreting overflowing exponents as floats', function (string $value): void {
    expect((new NumericFilter())->accept($value))->toBe(is_numeric($value));
})->with([
    '', '+', '-', '.', '+.', '-.', '0', '00', '1.', '.1', '+.1', '-.1',
    '1e2', '1.e+2', '.1E-2', '+.1e3', '1e', '1e+', '1e-', '1e2.3', '1e2e3',
    '1.2.3', '1 2', '1e 2', '0x10', '0b10', '1_000', 'NAN', 'INF',
    "\t42\f", "\x0042", "42\x00", '1e' . str_repeat('9', 100),
]);
