<?php

declare(strict_types=1);

use Componenta\Filter\UniqueFilter;

it('keeps concurrent iterators independent', function (): void {
    $filter = new UniqueFilter([1, 1, 2, 2]);

    $first = $filter->getIterator();
    $second = $filter->getIterator();

    expect($first->current())->toBe(1)
        ->and($second->current())->toBe(1);

    $first->next();
    $second->next();

    expect($first->current())->toBe(2)
        ->and($second->current())->toBe(2);
});

it('resets uniqueness between complete iterations', function (): void {
    $filter = new UniqueFilter([1, 1, 2, 2]);

    expect($filter->toArray())->toBe([1, 2])
        ->and($filter->toArray())->toBe([1, 2]);
});

it('preserves the direct accept state reset performed by getIterator', function (): void {
    $filter = new UniqueFilter([2]);

    expect($filter->accept(1))->toBeTrue()
        ->and($filter->accept(1))->toBeFalse();

    $filter->getIterator();

    expect($filter->accept(1))->toBeTrue();
});
