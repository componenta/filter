<?php

declare(strict_types=1);

use Componenta\Filter\GreaterThanEqualsFilter;
use Componenta\Filter\LessThanEqualsFilter;
use Componenta\Filter\LessThanFilter;

it('honors constructor iterable binding in threshold comparison filters', function (): void {
    expect((new LessThanFilter(3, [1, 3, 4]))->toArray())->toBe([1])
        ->and((new LessThanEqualsFilter(3, [1, 3, 4]))->toArray())->toBe([1, 3])
        ->and((new GreaterThanEqualsFilter(3, [1, 3, 4]))->toArray())->toBe([3, 4]);
});
