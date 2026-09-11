<?php

declare(strict_types=1);

use Componenta\Filter\NumericFilter;

it('accepts finite numeric values and numeric strings', function (): void {
    $filter = new NumericFilter();

    expect($filter->accept(1))->toBeTrue()
        ->and($filter->accept(-1.25))->toBeTrue()
        ->and($filter->accept('90071992547409931234567890'))->toBeTrue()
        ->and($filter->accept('1.25e100'))->toBeTrue();
});

it('rejects non-finite floating-point values', function (): void {
    $filter = new NumericFilter();

    expect($filter->accept(NAN))->toBeFalse()
        ->and($filter->accept(INF))->toBeFalse()
        ->and($filter->accept(-INF))->toBeFalse();
});

it('rejects non-numeric values', function (): void {
    $filter = new NumericFilter();

    expect($filter->accept('one'))->toBeFalse()
        ->and($filter->accept([]))->toBeFalse()
        ->and($filter->accept(new stdClass()))->toBeFalse();
});
