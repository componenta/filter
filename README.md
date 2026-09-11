# Componenta Filter

Composable filter objects for iterable data and reflection/class discovery.

## Installation

```bash
composer require componenta/filter
```

## Requirements

- PHP 8.4+
- `componenta/arrayable`

## Related Packages

| Package | Why it matters here |
|---|---|
| `componenta/arrayable` | Filters can expose their result through `toArray()`. |
| `componenta/class-finder` | Uses filters to select discovered classes, attributes, and reflection targets. |
| `componenta/iterator` | Can combine filtering with replayable iteration. |

## What It Provides

- `FilterInterface`: iterable filter contract with `accept()` and `toArray()`.
- `AbstractFilter`: base implementation for filtering an iterable source.
- `FilterableInterface` and `Filterable`: immutable filter-chain support.
- Concrete filters for scalars, arrays, strings, class names, reflection objects, files, ranges, callbacks, and composition.

## Basic Usage

```php
use Componenta\Filter\StringFilter;

$filter = new StringFilter(['one', 2, 'three']);

$filter->toArray(); // ['one', 'three']
```

Keys are not preserved by default:

```php
$filter->toArray(preserveKeys: true);
```

## Custom Criteria

```php
use Componenta\Filter\CallbackFilter;

$filter = new CallbackFilter(
    static fn(mixed $value, string|int|null $key): bool => is_int($value) && $value > 10,
    [5, 15, 20],
);

$filter->toArray(); // [15, 20]
```

## Filter Chains

Objects using `Filterable` return new instances when filters are added or removed.

```php
$next = $filterable->withFilter($filter);
$sameWithout = $next->withoutFilter($filter);
```

`Filterable::accept()` uses AND semantics: every registered filter must accept the value. `OneOfFilter::accept()` uses OR semantics and returns `false` when no alternatives are configured.

## Collection-aware Filters

`PercentageFilter` depends on the size of the complete collection. Iterate it or call `toArray()`; calling `accept()` directly throws `LogicException` because a single value does not provide enough context to determine a percentage.

`MergingFilter` concatenates the iterable results of its inner filters. Its `accept()` method uses OR semantics: a value is accepted when at least one inner filter accepts it. `withIterable()` applies the new source to every inner filter and safely replays one-shot iterables when necessary.

## Validation

Invalid filter configuration is rejected early with `InvalidArgumentException` where the filter cannot operate safely, including invalid ranges, regular expressions, `filter_var()` configurations, probabilities, percentages, and typed class/string lists.

## Development

Install development dependencies and run the Pest suite:

```bash
composer install
composer test
```

The CI suite validates Composer metadata, lints all PHP sources and tests, and runs Pest on PHP 8.4 and 8.5. A manual mutation-audit workflow is also available for checking test sensitivity.
