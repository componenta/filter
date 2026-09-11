<?php

declare(strict_types=1);

use Componenta\Filter\CollectionFilterInterface;
use Componenta\Filter\PredicateInterface;
use Componenta\Filter\UniqueFilter;

it('is a collection-only operator', function (): void {
    $filter = new UniqueFilter([1, 1, 2]);

    expect($filter)->toBeInstanceOf(CollectionFilterInterface::class)
        ->and($filter)->not->toBeInstanceOf(PredicateInterface::class)
        ->and(method_exists($filter, 'accept'))->toBeFalse();
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
