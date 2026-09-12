<?php

declare(strict_types=1);

use Componenta\Filter\RecursiveFilter;
use Componenta\Filter\StringFilter;

it('uses the documented default maximum depth of sixty-four', function (): void {
    $nest = static function (int $depth): array {
        $value = 'leaf';

        for ($level = 0; $level < $depth; $level++) {
            $value = [$value];
        }

        return $value;
    };

    $atLimit = new RecursiveFilter(
        new StringFilter(),
        iterable: [$nest(64)],
    );
    $pastLimit = new RecursiveFilter(
        new StringFilter(),
        iterable: [$nest(65)],
    );

    expect($atLimit->toArray())->toBe(['leaf'])
        ->and(fn() => $pastLimit->toArray())
        ->toThrow(OverflowException::class, 'Maximum recursive filter depth of 64 exceeded');
});
