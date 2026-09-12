<?php

declare(strict_types=1);

use Componenta\Filter\LengthRangeFilter;

it('rejects a negative maximum length even when the minimum is zero', function (): void {
    new LengthRangeFilter(0, -1);
})->throws(InvalidArgumentException::class);
