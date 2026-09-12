<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('rejects recursively referenced arrays without recursing indefinitely', function (): void {
    $value = [];
    $value['self'] = &$value;

    expect((new FilterVarFilter(
        FILTER_VALIDATE_INT,
        FILTER_REQUIRE_ARRAY,
    ))->accept($value))->toBeFalse();
});

it('does not mistake shared array references for a cycle', function (): void {
    $shared = ['1', '2'];
    $value = [
        'left' => &$shared,
        'right' => &$shared,
    ];

    expect((new FilterVarFilter(
        FILTER_VALIDATE_INT,
        FILTER_REQUIRE_ARRAY,
    ))->accept($value))->toBeTrue();
});
