<?php

declare(strict_types=1);

use Componenta\Filter\BetweenFilter;
use Componenta\Filter\GreaterThanEqualsFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\LessThanEqualsFilter;
use Componenta\Filter\LessThanFilter;
use Componenta\Filter\RangeFilter;

it('retains exact comparisons across integer fast paths and mixed numeric representations', function (): void {
    $low = PHP_INT_MIN;
    $high = PHP_INT_MAX;
    $values = [$low, (string) $low, $low + 1, 0, $high - 1, $high, (string) $high, '9223372036854775808'];

    foreach ($values as $value) {
        $above = $value === '9223372036854775808';
        expect((new RangeFilter($low, $high))->accept($value))->toBe(!$above)
            ->and((new BetweenFilter($low, $high))->accept($value))->toBe(!$above)
            ->and((new GreaterThanFilter($high))->accept($value))->toBe($above)
            ->and((new LessThanEqualsFilter($high))->accept($value))->toBe(!$above);
    }

    expect((new GreaterThanEqualsFilter($low))->accept($low))->toBeTrue()
        ->and((new LessThanFilter($low))->accept($low))->toBeFalse()
        ->and((new BetweenFilter($low, $high, false))->accept($low))->toBeFalse()
        ->and((new BetweenFilter($low, $high, false))->accept($high))->toBeFalse();
})->skip(PHP_INT_SIZE !== 8, '64-bit boundary vectors');

it('updates prepared bounds when immutable configuration is replaced', function (): void {
    $range = new RangeFilter('0.1000000000000000000001', '0.1000000000000000000003');
    $changed = $range->withMax('0.1000000000000000000005');

    expect($range->accept('0.1000000000000000000004'))->toBeFalse()
        ->and($changed->accept('0.1000000000000000000004'))->toBeTrue()
        ->and($changed->withIterable(['0.1000000000000000000004', 'invalid'])->toArray())->toBe(['0.1000000000000000000004']);

    $threshold = new GreaterThanFilter('1e100000000000000000000');
    expect($threshold->withThreshold('9e99999999999999999999')->accept('1e100000000000000000000'))->toBeTrue()
        ->and($threshold->accept('1e100000000000000000000'))->toBeFalse();
});
