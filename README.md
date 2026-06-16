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

Objects using `Filterable` should return new instances when filters are added or removed.

```php
$next = $filterable->withFilter($filter);
$sameWithout = $next->withoutFilter($filter);
```

`accept()` uses AND semantics: every registered filter must accept the value.
