<?php

declare(strict_types=1);

use Componenta\Filter\CallbackFilter;
use Componenta\Filter\Filterable;
use Componenta\Filter\FilterableInterface;
use Componenta\Filter\FilterInterface;

it('initializes directly from a single filter instance', function (): void {
    $filter = new CallbackFilter(static fn(mixed $value): bool => $value === 'accepted');
    $filterable = new FilterableFixture($filter);

    expect($filterable->hasFilter($filter))->toBeTrue()
        ->and($filterable->accept('accepted'))->toBeTrue()
        ->and($filterable->accept('rejected'))->toBeFalse();
});

it('returns new instances when filters are added or removed', function (): void {
    $filter = new CallbackFilter(static fn(mixed $value): bool => is_int($value));
    $filterable = new FilterableFixture();

    $withFilter = $filterable->withFilter($filter);
    $withoutFilter = $withFilter->withoutFilter($filter);

    expect($withFilter)->not->toBe($filterable)
        ->and($withoutFilter)->not->toBe($withFilter)
        ->and($filterable->hasFilter($filter))->toBeFalse()
        ->and($withFilter->hasFilter($filter))->toBeTrue()
        ->and($withoutFilter->hasFilter($filter))->toBeFalse();
});

it('honors prepend order when evaluating filters', function (): void {
    $calls = [];

    $first = new CallbackFilter(function () use (&$calls): bool {
        $calls[] = 'first';

        return true;
    });
    $prepended = new CallbackFilter(function () use (&$calls): bool {
        $calls[] = 'prepended';

        return true;
    });

    (new FilterableFixture())
        ->withFilter($first)
        ->withFilter($prepended, prepend: true)
        ->accept('value');

    expect($calls)->toBe(['prepended', 'first']);
});

it('requires every filter to accept a value', function (): void {
    $filterable = (new FilterableFixture())
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => is_int($value)))
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => $value > 10));

    expect($filterable->accept('11'))->toBeFalse()
        ->and($filterable->accept(5))->toBeFalse()
        ->and($filterable->accept(15))->toBeTrue();
});

it('reindexes the public filter list after removing a middle filter', function (): void {
    $first = new CallbackFilter(static fn(): bool => true);
    $middle = new CallbackFilter(static fn(): bool => true);
    $last = new CallbackFilter(static fn(): bool => true);

    $filterable = (new FilterableFixture([$first, $middle, $last]))
        ->withoutFilter($middle);

    expect($filterable->getFilters())->toBe([$first, $last]);
});

it('rejects invalid filters during construction', function (): void {
    new FilterableFixture(['not-a-filter']);
})->throws(InvalidArgumentException::class);

final class FilterableFixture implements FilterableInterface
{
    use Filterable;

    /**
     * @param iterable<FilterInterface>|FilterInterface $filters
     */
    public function __construct(iterable|FilterInterface $filters = [])
    {
        $this->initFilters($filters);
    }
}
