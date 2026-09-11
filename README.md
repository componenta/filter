# Componenta Filter

Composable predicates and iterable collection filters for PHP 8.4+.

## Installation

```bash
composer require componenta/filter
```

## Requirements

- PHP 8.4+
- `componenta/arrayable`

## Contracts

Version 2 separates single-value predicates from collection operations.

- `PredicateInterface` exposes only `accept()` and can be used anywhere a single value can be evaluated independently.
- `CollectionFilterInterface` exposes iterable filtering/transformation through `getIterator()`, `withIterable()`, and `toArray()`.
- `FilterInterface` extends both interfaces and represents a predicate-backed collection filter.
- `AbstractCollectionFilter` provides immutable iterable binding and `toArray()` for collection-only operators.
- `AbstractFilter` extends `AbstractCollectionFilter` and adds predicate-backed iteration.

This separation means collection-aware operators no longer expose fake predicate behavior.

## Basic Predicate-backed Filtering

```php
use Componenta\Filter\StringFilter;

$filter = new StringFilter(['one', 2, 'three']);

$filter->accept('value'); // true
$filter->toArray();       // ['one', 'three']
```

Keys are not preserved by default:

```php
$filter->toArray(preserveKeys: true);
```

## Pure Predicates

Predicate composition no longer requires iterable behavior:

```php
use Componenta\Filter\ChainableFilter;
use Componenta\Filter\PredicateInterface;

$positiveInteger = new class implements PredicateInterface {
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_int($value) && $value > 0;
    }
};

$filter = ChainableFilter::create($positiveInteger);
$filter->accept(10); // true
```

`Filterable`, `ChainableFilter`, `OneOfFilter`, `NotFilter`, and `RecursiveFilter` depend on `PredicateInterface`, not on `FilterInterface`.

## Collection-only Operators

`PercentageFilter` is a `CollectionFilterInterface` only because its result depends on the size and position of the whole iterable:

```php
use Componenta\Filter\PercentageFilter;

$filter = new PercentageFilter(50, ['a', 'b', 'c', 'd']);
$filter->toArray(); // ['a', 'b']
```

It intentionally has no `accept()` method.

`MergingFilter` is also collection-only. It concatenates results from arbitrary `CollectionFilterInterface` implementations, including other collection-only operators:

```php
use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\PercentageFilter;

$filter = new MergingFilter(
    new IntFilter([1, 'two']),
    new PercentageFilter(50, ['a', 'b', 'c', 'd']),
);

$filter->toArray(); // [1, 'a', 'b']
```

`withIterable()` applies a replacement source to every merged collection filter and safely replays one-shot iterables where necessary.

## Predicate Composition

Objects using `Filterable` return new instances when predicates are added or removed:

```php
$next = $filterable->withFilter($predicate);
$sameWithout = $next->withoutFilter($predicate);
```

`Filterable::accept()` uses AND semantics. `OneOfFilter::accept()` uses OR semantics and returns `false` when no predicates are configured.

## Validation

Invalid filter configuration is rejected early with `InvalidArgumentException` where the filter cannot operate safely, including invalid ranges, regular expressions, `filter_var()` configurations, probabilities, percentages, and typed class/string lists.

## Breaking Changes from 1.x

- `FilterInterface` is now the intersection of `PredicateInterface` and `CollectionFilterInterface`.
- `PercentageFilter` no longer extends `AbstractFilter` and no longer has `accept()`.
- `MergingFilter` no longer implements `FilterInterface` and no longer has `accept()`.
- `MergingFilter` accepts `CollectionFilterInterface` instances, not only predicate-backed filters.
- Predicate composition APIs accept `PredicateInterface`, so custom predicates no longer need iterable methods.
- `AbstractFilter` now extends `AbstractCollectionFilter`.

## Development

Install development dependencies and run Pest:

```bash
composer install
composer test
```

CI validates Composer metadata, lints all PHP sources and tests, and runs Pest on PHP 8.4 and 8.5. A manual mutation-audit workflow is available for checking test sensitivity.
