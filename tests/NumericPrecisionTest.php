<?php

declare(strict_types=1);

use Componenta\Filter\BetweenFilter;
use Componenta\Filter\GreaterThanEqualsFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\LessThanEqualsFilter;
use Componenta\Filter\LessThanFilter;
use Componenta\Filter\RangeFilter;

it('keeps adjacent integers above the IEEE-754 exact range distinct', function (): void {
    $exact = '9007199254740993';

    expect((new BetweenFilter($exact, $exact))->accept($exact))->toBeTrue()
        ->and((new BetweenFilter($exact, $exact))->accept('9007199254740992'))->toBeFalse()
        ->and((new BetweenFilter($exact, $exact))->accept('9007199254740994'))->toBeFalse()
        ->and((new RangeFilter($exact, $exact))->accept($exact))->toBeTrue();
});

it('compares arbitrarily large integer strings exactly', function (): void {
    $threshold = '922337203685477580812345678901234567890';

    expect((new GreaterThanFilter($threshold))->accept('922337203685477580812345678901234567891'))->toBeTrue()
        ->and((new GreaterThanFilter($threshold))->accept($threshold))->toBeFalse()
        ->and((new GreaterThanEqualsFilter($threshold))->accept($threshold))->toBeTrue()
        ->and((new LessThanFilter($threshold))->accept('922337203685477580812345678901234567889'))->toBeTrue()
        ->and((new LessThanEqualsFilter($threshold))->accept($threshold))->toBeTrue();
});

it('compares exact decimal and scientific numeric strings without float rounding', function (): void {
    $filter = new BetweenFilter('0.1000000000000000000001', '0.1000000000000000000003');

    expect($filter->accept('0.1000000000000000000002'))->toBeTrue()
        ->and($filter->accept('0.1000000000000000000004'))->toBeFalse()
        ->and((new GreaterThanFilter('1e100'))->accept('9e99'))->toBeFalse()
        ->and((new GreaterThanFilter('1e100'))->accept('1.0000000000000000001e100'))->toBeTrue();
});

it('supports numeric bounds expressed with very large exponents', function (): void {
    $threshold = '1e100000000000000000000';

    expect((new GreaterThanFilter($threshold))->accept('2e100000000000000000000'))->toBeTrue()
        ->and((new LessThanFilter($threshold))->accept('9e99999999999999999999'))->toBeTrue()
        ->and((new GreaterThanEqualsFilter($threshold))->accept($threshold))->toBeTrue();
});
