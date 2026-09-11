<?php

declare(strict_types=1);

use Componenta\Filter\CallbackFilter;
use Componenta\Filter\Filterable;
use Componenta\Filter\FilterableInterface;
use Componenta\Filter\PredicateInterface;

it('initializes directly from a single predicate instance', function (): void {
    $filter = new CallbackFilter(static fn(mixed $value): bool => $value === 'accepted');
    $filterable = new FilterableFixture($filter);

    expect($filterable->hasFilter($filter))->toBeTrue()
        ->and($filterable->accept('accepted'))->toBeTrue()
        ->and($filterable->accept('rejected'))->toBeFalse();
});

it('accepts pure predicates without iterable filter behavior', function (): void {
    $predicate = new class implements PredicateInterface {
        public function accept(mixed $value, string|int|null $key = null): bool
        {
            return $value === 42;
        }
    };

    $filterable = new FilterableFixture($predicate);

    expect($filterable->accept(42))->toBeTrue()
        ->and($filterable->accept(41))->toBeFalse();
});

it('returns new instances when predicates are added or removed', function (): void {
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

it('honors prepend order when evaluating predicates', function (): void {
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

it('requires every predicate to accept a value', function (): void {
    $filterable = (new FilterableFixture())
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => is_int($value)))
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => $value > 10));

    expect($filterable->accept('11'))->toBeFalse()
        ->and($filterable->accept(5))->toBeFalse()
        ->and($filterable->accept(15))->toBeTrue();
});

it('reindexes the public predicate list after removing a middle predicate', function (): void {
    $first = new CallbackFilter(static fn(): bool => true);
    $middle = new CallbackFilter(static fn(): bool => true);
    $last = new CallbackFilter(static fn(): bool => true);

    $filterable = (new FilterableFixture([$first, $middle, $last]))
        ->withoutFilter($middle);

    expect($filterable->getFilters())->toBe([$first, $last]);
});

it('rejects invalid predicates during construction', function (): void {
    new FilterableFixture(['not-a-predicate']);
})->throws(InvalidArgumentException::class);

final class FilterableFixture implements FilterableInterface
{
    use Filterable;

    /**
     * @param iterable<PredicateInterface>|PredicateInterface $filters
     */
    public function __construct(iterable|PredicateInterface $filters = [])
    {
        $this->initFilters($filters);
    }
}
