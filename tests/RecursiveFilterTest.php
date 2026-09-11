<?php

declare(strict_types=1);

use Componenta\Filter\RecursiveFilter;
use Componenta\Filter\StringFilter;

it('preserves nested keys when yielding nested arrays', function (): void {
    $filter = new RecursiveFilter(
        new StringFilter(),
        yieldNestedAsArray: true,
        iterable: [
            'group' => [
                'first' => 'one',
                'second' => 2,
                'third' => 'three',
            ],
        ],
    );

    expect($filter->toArray(true))->toBe([
        'group' => [
            'first' => 'one',
            'third' => 'three',
        ],
    ]);
});
