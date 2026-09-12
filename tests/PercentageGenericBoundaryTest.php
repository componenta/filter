<?php

declare(strict_types=1);

use Componenta\Filter\PercentageFilter;

it('uses the same floor calculation for generic iterables near one hundred percent', function (): void {
    $source = (static function (): Generator {
        yield from range(1, 100);
    })();

    expect((new PercentageFilter(99, $source))->toArray())->toBe(range(1, 99));
});
