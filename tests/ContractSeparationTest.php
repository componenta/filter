<?php

declare(strict_types=1);

use Componenta\Filter\ChainableFilter;
use Componenta\Filter\CollectionFilterInterface;
use Componenta\Filter\FilterInterface;
use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\NotFilter;
use Componenta\Filter\PercentageFilter;
use Componenta\Filter\PredicateInterface;
use Componenta\Filter\RecursiveFilter;
use Componenta\Filter\UniqueFilter;

it('separates predicate-backed filters from collection-only operators', function (): void {
    $filter = new IntFilter([1, 'two']);
    $percentage = new PercentageFilter(50, [1, 2, 3, 4]);
    $unique = new UniqueFilter([1, 1, 2]);
    $merging = new MergingFilter($filter, $percentage, $unique);

    expect($filter)->toBeInstanceOf(PredicateInterface::class)
        ->and($filter)->toBeInstanceOf(CollectionFilterInterface::class)
        ->and($filter)->toBeInstanceOf(FilterInterface::class)
        ->and($percentage)->toBeInstanceOf(CollectionFilterInterface::class)
        ->and($percentage)->not->toBeInstanceOf(PredicateInterface::class)
        ->and($percentage)->not->toBeInstanceOf(FilterInterface::class)
        ->and($unique)->toBeInstanceOf(CollectionFilterInterface::class)
        ->and($unique)->not->toBeInstanceOf(PredicateInterface::class)
        ->and($unique)->not->toBeInstanceOf(FilterInterface::class)
        ->and($merging)->toBeInstanceOf(CollectionFilterInterface::class)
        ->and($merging)->not->toBeInstanceOf(PredicateInterface::class)
        ->and($merging)->not->toBeInstanceOf(FilterInterface::class);
});

it('allows predicate composition without requiring iterable filter behavior', function (): void {
    $predicate = new class implements PredicateInterface {
        public function accept(mixed $value, string|int|null $key = null): bool
        {
            return is_int($value) && $value > 10;
        }
    };

    expect(ChainableFilter::create($predicate)->accept(11))->toBeTrue()
        ->and(ChainableFilter::create($predicate)->accept(10))->toBeFalse()
        ->and((new NotFilter($predicate))->accept(10))->toBeTrue()
        ->and((new RecursiveFilter($predicate))->accept(11))->toBeTrue();
});

it('merges arbitrary collection filters without inventing predicate semantics', function (): void {
    $filter = new MergingFilter(
        new IntFilter([1, 'two']),
        new PercentageFilter(50, ['a', 'b', 'c', 'd']),
        new UniqueFilter([1, 1, 2]),
    );

    expect($filter->toArray())->toBe([1, 'a', 'b', 1, 2]);
});
