<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\OneOfFilter;
use Componenta\Filter\StringFilter;

it('rejects values when no alternative filters are configured', function (): void {
    $filter = new OneOfFilter([], ['value', 1]);

    expect($filter->accept('value'))->toBeFalse()
        ->and($filter->toArray())->toBe([]);
});

it('accepts a value when at least one alternative filter accepts it', function (): void {
    $filter = new OneOfFilter([new IntFilter(), new StringFilter()]);

    expect($filter->accept(1))->toBeTrue()
        ->and($filter->accept('one'))->toBeTrue()
        ->and($filter->accept([]))->toBeFalse();
});
