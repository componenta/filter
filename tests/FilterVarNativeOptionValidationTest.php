<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('rejects native integer range options that would emit warnings at runtime', function (): void {
    new FilterVarFilter(
        FILTER_VALIDATE_INT,
        ['options' => ['max_range' => new stdClass()]],
    );
})->throws(InvalidArgumentException::class);

it('rejects native float options that would throw at runtime', function (): void {
    new FilterVarFilter(
        FILTER_VALIDATE_FLOAT,
        ['options' => ['decimal' => '..']],
    );
})->throws(InvalidArgumentException::class);
