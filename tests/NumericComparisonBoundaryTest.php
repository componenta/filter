<?php

declare(strict_types=1);

use Componenta\Filter\GreaterThanEqualsFilter;
use Componenta\Filter\LessThanEqualsFilter;
use Componenta\Filter\LessThanFilter;
use Componenta\Filter\RangeFilter;

it('distinguishes exact threshold boundaries in scalar comparison predicates', function (): void {
    $threshold = '1000000000000000000000000000000';

    expect((new LessThanFilter($threshold))->accept($threshold))->toBeFalse()
        ->and((new LessThanEqualsFilter($threshold))->accept('1000000000000000000000000000001'))->toBeFalse()
        ->and((new GreaterThanEqualsFilter($threshold))->accept('999999999999999999999999999999'))->toBeFalse()
        ->and((new RangeFilter('10', '20'))->accept('21'))->toBeFalse();
});
