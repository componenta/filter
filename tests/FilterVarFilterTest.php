<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('distinguishes a valid false boolean from validation failure', function (): void {
    $filter = new FilterVarFilter(FILTER_VALIDATE_BOOLEAN);

    expect($filter->accept(false))->toBeTrue()
        ->and($filter->accept('false'))->toBeTrue()
        ->and($filter->accept('not-a-boolean'))->toBeFalse();
});

it('rejects an unknown filter id at construction time', function (): void {
    new FilterVarFilter(PHP_INT_MAX);
})->throws(InvalidArgumentException::class);
