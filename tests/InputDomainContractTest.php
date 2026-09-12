<?php

declare(strict_types=1);

use Componenta\Filter\ArrayDiffFilter;
use Componenta\Filter\ArrayIntersectFilter;
use Componenta\Filter\PropertyExistsFilter;
use Componenta\Filter\RangeFilter;

it('rejects values outside predicate input domains', function (): void {
    expect((new ArrayIntersectFilter([1]))->accept('1'))->toBeFalse()
        ->and((new ArrayDiffFilter([1]))->accept('1'))->toBeFalse()
        ->and((new RangeFilter(1, 3))->accept('not numeric'))->toBeFalse()
        ->and((new PropertyExistsFilter('status'))->accept('not an object'))->toBeFalse();
});
