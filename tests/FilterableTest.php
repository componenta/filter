<?php

declare(strict_types=1);

use Componenta\Filter\CallbackFilter;
use Componenta\Filter\Filterable;
use Componenta\Filter\FilterableInterface;
use Componenta\Filter\FilterInterface;

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

it('requires every filter to accept a value', function (): void {
    $filterable = (new FilterableFixture())
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => is_int($value)))
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => $value > 10));

    expect($filterable->accept('11'))->toBeFalse()
        ->and($filterable->accept(5))->toBeFalse()
        ->and($filterable->accept(15))->toBeTrue();
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
