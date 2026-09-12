<?php

declare(strict_types=1);

use Componenta\Filter\UniqueFilter;

it('keeps type-distinct values unique by default', function (): void {
    expect((new UniqueFilter([1, '1', 1, '1']))->toArray())->toBe([1, '1']);
});

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
