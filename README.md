# Componenta Filter

Composable predicates and iterable collection filters for PHP 8.4+.

## Installation

```bash
composer require componenta/filter
```

## Requirements

- PHP 8.4+
- `componenta/arrayable`
- `componenta/iterator`

## Contracts

Version 2 separates single-value predicates from collection operations.

- `PredicateInterface` exposes only `accept()` and is used when one value can be evaluated without collection context.
- `CollectionFilterInterface` exposes collection processing through `getIterator()`, `withIterable()`, and `toArray()`.
- `FilterInterface` extends both interfaces and represents a predicate-backed collection filter.
- `AbstractCollectionFilter` provides immutable iterable binding and `toArray()` for collection-only operators.
- `AbstractFilter` extends `AbstractCollectionFilter` and adds predicate-backed iteration.

## Predicate-backed Filters

```php
use Componenta\Filter\StringFilter;

$filter = new StringFilter(['one', 2, 'three']);

$filter->accept('value'); // true
$filter->toArray();       // ['one', 'three']
```

String-oriented predicates accept only `string` and `Stringable` values. Scalars such as integers and booleans are not silently cast to strings.

## Pure Predicates and Composition

Custom predicates do not need iterable behavior:

```php
use Componenta\Filter\ChainableFilter;
use Componenta\Filter\PredicateInterface;

$positiveInteger = new class implements PredicateInterface {
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_int($value) && $value > 0;
    }
};

$filter = new ChainableFilter($positiveInteger);
$filter->accept(10); // true
```

`Filterable`, `ChainableFilter`, `OneOfFilter`, `NotFilter`, and `RecursiveFilter` depend on `PredicateInterface`. `Filterable`/`ChainableFilter` use AND semantics; `OneOfFilter` uses OR semantics and an empty `OneOfFilter` rejects every value.

## Collection-only Operators

The following operators intentionally do not implement `PredicateInterface`:

- `PercentageFilter`: the result depends on collection size and position.
- `UniqueFilter`: uniqueness depends on values already observed in the current traversal.
- `MergingFilter`: concatenates results from multiple `CollectionFilterInterface` instances.

```php
use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\PercentageFilter;
use Componenta\Filter\UniqueFilter;

$filter = new MergingFilter(
    new IntFilter([1, 'two']),
    new PercentageFilter(50, ['a', 'b', 'c', 'd']),
    new UniqueFilter([1, 1, 2]),
);

$filter->toArray(); // [1, 'a', 'b', 1, 2]
```

`MergingFilter::withIterable()` uses `ReplayableIterator` to fan out one-shot sources lazily. Binding a generator does not consume it immediately, and each inner filter gets an independent replay cursor.

## Exact Numeric Predicates

`NumericFilter`, `BetweenFilter`, `RangeFilter`, `GreaterThan*`, `LessThan*`, `MultipleOfFilter`, and the parity filters do not collapse numeric strings to `float` unnecessarily.

Large integers, long decimal strings, and scientific notation can therefore be compared without the `2^53` precision loss of IEEE-754 conversion. `NAN` and infinite values are rejected by value-oriented numeric predicates. Actual PHP float inputs keep their native floating-point precision; `MultipleOfFilter` uses a bounded tolerance only when the tested value itself is a float.

## Date Ranges

`DateRangeFilter` accepts `DateTimeInterface` or absolute ISO-like date strings. It captures a timezone at construction for local strings, preserves microsecond precision, and does not accept relative expressions such as `tomorrow` or silently normalized impossible dates.

```php
$range = new DateRangeFilter(
    '2026-01-01 00:00:00',
    '2026-12-31 23:59:59',
    timezone: new DateTimeZone('Europe/Copenhagen'),
);
```

## Recursive Filtering

`RecursiveFilter` detects iterable-object cycles and also enforces `maxDepth` (default `64`) so recursive arrays cannot recurse indefinitely.

```php
$filter = new RecursiveFilter($predicate, maxDepth: 32);
```

## Random Filtering

`RandomFilter` uses an isolated `Random\Randomizer` instead of the process-global `mt_rand()` state. Inject a `Randomizer` when deterministic seeded behavior is needed.

## Validation

Invalid configuration is rejected early with `InvalidArgumentException` where safe execution would otherwise be impossible. This includes invalid numeric/date ranges, regular expressions, `filter_var()` IDs/options, probabilities, percentages, and typed class/string/key lists.

## Breaking Changes from 1.x

- `FilterInterface` is now the intersection of `PredicateInterface` and `CollectionFilterInterface`.
- `PercentageFilter`, `UniqueFilter`, and `MergingFilter` are collection-only and have no `accept()` method.
- Predicate composition APIs accept `PredicateInterface`; custom predicates no longer need iterable methods.
- `MergingFilter` accepts arbitrary `CollectionFilterInterface` implementations and replays one-shot inputs lazily.
- String predicates no longer cast arbitrary scalars to strings.
- Numeric range thresholds support `int|float|string` and exact decimal/scientific comparison.
- `DateRangeFilter` uses absolute deterministic parsing, a captured timezone, and microsecond precision.
- `RandomFilter` no longer consumes global `mt_rand()` state.
- `RecursiveFilter` has bounded recursion and cycle detection.

## Development

```bash
composer install
composer test
```

CI validates Composer metadata, lints all PHP sources and tests, and runs Pest on PHP 8.4 and 8.5. A manual mutation-audit workflow is available for checking test sensitivity.
