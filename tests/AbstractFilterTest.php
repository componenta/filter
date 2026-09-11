<?php

declare(strict_types=1);

use Componenta\Filter\StringFilter;

it('iterates only accepted values', function (): void {
    $stringable = new class implements Stringable {
        public function __toString(): string
        {
            return 'three';
        }
    };

    $filter = new StringFilter([
        'first' => 'one',
        'second' => 2,
        'third' => $stringable,
    ]);

    expect($filter->toArray())->toBe(['one', $stringable])
        ->and($filter->toArray(true))->toBe([
            'first' => 'one',
            'third' => $stringable,
        ]);
});

it('returns a new filter when replacing the iterable', function (): void {
    $filter = new StringFilter(['one']);
    $changed = $filter->withIterable([1, 'two']);

    expect($changed)->not->toBe($filter)
        ->and($filter->toArray())->toBe(['one'])
        ->and($changed->toArray())->toBe(['two']);
});
