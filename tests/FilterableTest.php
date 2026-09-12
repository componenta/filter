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

it('checks filter membership by predicate identity', function (): void {
    $filter = new CallbackFilter(static fn(mixed $value): bool => is_int($value));
    $equivalentButDistinct = new CallbackFilter(static fn(mixed $value): bool => is_int($value));
    $filterable = new FilterableFixture($filter);

    expect($filterable->hasFilter($filter))->toBeTrue()
        ->and($filterable->hasFilter($equivalentButDistinct))->toBeFalse();
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

it('adds and removes predicates without mutating earlier instances', function (): void {
    $filter = new CallbackFilter(static fn(mixed $value): bool => is_int($value));
    $filterable = new FilterableFixture();

    $withFilter = $filterable->withFilter($filter);
    $withoutFilter = $withFilter->withoutFilter($filter);

    expect($withFilter)->not->toBe($filterable)
        ->and($withoutFilter)->not->toBe($withFilter)
        ->and($filterable->accept('not-an-int'))->toBeTrue()
        ->and($withFilter->accept('not-an-int'))->toBeFalse()
        ->and($withoutFilter->accept('not-an-int'))->toBeTrue();
});

it('appends predicates by default and preserves short-circuit order', function (): void {
    $calls = [];
    $first = new CallbackFilter(static function () use (&$calls): bool {
        $calls[] = 'first';
        return true;
    });
    $second = new CallbackFilter(static function () use (&$calls): bool {
        $calls[] = 'second';
        return false;
    });

    $filterable = (new FilterableFixture($first))->withFilter($second);

    expect($filterable->accept('value'))->toBeFalse()
        ->and($calls)->toBe(['first', 'second']);
});

it('evaluates a prepended predicate first and short-circuits on rejection', function (): void {
    $mustNotRun = new CallbackFilter(static function (): bool {
        throw new RuntimeException('later predicate must be short-circuited');
    });
    $reject = new CallbackFilter(static fn(): bool => false);

    $filterable = (new FilterableFixture())
        ->withFilter($mustNotRun)
        ->withFilter($reject, prepend: true);

    expect($filterable->accept('value'))->toBeFalse();
});

it('requires every predicate to accept a value', function (): void {
    $filterable = (new FilterableFixture())
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => is_int($value)))
        ->withFilter(new CallbackFilter(static fn(mixed $value): bool => $value > 10));

    expect($filterable->accept('11'))->toBeFalse()
        ->and($filterable->accept(5))->toBeFalse()
        ->and($filterable->accept(15))->toBeTrue();
});

it('removes only the requested predicate from evaluation', function (): void {
    $integer = new CallbackFilter(static fn(mixed $value): bool => is_int($value));
    $positive = new CallbackFilter(static fn(int $value): bool => $value > 0);
    $even = new CallbackFilter(static fn(int $value): bool => $value % 2 === 0);

    $withAll = new FilterableFixture([$integer, $positive, $even]);
    $withoutPositive = $withAll->withoutFilter($positive);

    expect($withAll->accept(-2))->toBeFalse()
        ->and($withoutPositive->accept(-2))->toBeTrue()
        ->and($withoutPositive->accept(-3))->toBeFalse()
        ->and($withoutPositive->accept('2'))->toBeFalse();
});

it('rejects invalid predicates during construction', function (): void {
    new FilterableFixture(['not-a-predicate']);
})->throws(InvalidArgumentException::class);

it('rejects invalid predicates from traversables with non-scalar keys', function (): void {
    $key = new stdClass();
    $filters = (static function () use ($key): Generator {
        yield $key => 'not-a-predicate';
    })();

    new FilterableFixture($filters);
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
