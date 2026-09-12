<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('validates every leaf in nested required arrays', function (): void {
    $filter = new FilterVarFilter(
        FILTER_VALIDATE_INT,
        ['flags' => FILTER_REQUIRE_ARRAY],
    );

    expect($filter->accept([1, ['2', 3], [[4]]]))->toBeTrue()
        ->and($filter->accept([1, ['2', 'not-an-int'], [[4]]]))->toBeFalse()
        ->and($filter->accept([[1], ['2'], [['not-an-int']]]))->toBeFalse();
});
