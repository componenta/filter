# Componenta Filter

Composable predicates and iterable collection filters for PHP 8.4+.

## Installation

```bash
composer require componenta/filter
```

## Requirements

- PHP 8.4+
- PHP extensions `ctype` and `filter`
- `componenta/arrayable`
- `componenta/iterator` ^1.1.2 (reentrant replay and interrupted source-read safety)

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

String-oriented predicates accept only `string` and `Stringable` values. Scalars such as integers and booleans are not silently cast to strings. Length predicates count bytes (not Unicode code points or grapheme clusters); case-insensitive string and extension predicates use ASCII case folding. Calling a Stringable object's conversion may execute user code.

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

- `PercentageFilter` keeps the first floor(count × percentage / 100) elements, using the shortest round-trip decimal representation of the supplied percentage. Arrays and consistently countable sources need not be fully consumed; a generic iterable is buffered for intermediate percentages. A 0% filter does not consume the source; a 100% filter streams it. Intermediate percentages require a finite source.
- `UniqueFilter` uses strict PHP equality, preserves first occurrences and retains separate traversal state. Scalars and object identity use indexes; complex values fall back to strict comparisons. Repeated NaN values remain distinct; signed floating-point zeros compare equal. Memory grows with the number of distinct values.
- `MergingFilter::withIterable()` defers opening iterator aggregates and rewinding iterators until the first traversal. Each inner filter receives an independent cursor over a shared replay cache. The cache retains visited elements, and aggregate wrapper chains are limited to 64 levels; exhaustion raises `OverflowException`. A merge over an infinite source cannot finish its first exhaustive traversal.

By default `toArray()` returns a list. With `preserveKeys: true`, later duplicate PHP array keys overwrite earlier values, numeric string keys follow PHP's array-key rules, and null iterator keys become empty strings without a deprecation. Raw iterators preserve their keys. Materialization with keys copies top-level value slots; arbitrary nested values and object identities are not deep-copied.

## Exact Numeric Predicates

`NumericFilter`, `BetweenFilter`, `RangeFilter`, `GreaterThan*`, `LessThan*`, `MultipleOfFilter`, and the parity filters do not collapse numeric strings to `float` unnecessarily.

Large integers, long decimal strings, and scientific notation can therefore be compared without the `2^53` precision loss of IEEE-754 conversion. `NAN` and infinite values are rejected by value-oriented numeric predicates. For comparisons and exact divisibility, finite float inputs are interpreted through their shortest round-trip decimal representation, rather than as exact binary fractions; parity of float inputs uses the actual floating-point integer value. Decimal conversion also works when `serialize_precision` cannot be changed or when the INI access functions are disabled. `MultipleOfFilter` uses a bounded tolerance only when the tested value itself is a float and conversion of the configured divisor to a float retains its normalized decimal value. Numeric strings allow PHP numeric whitespace (space, tab, LF, CR, vertical tab and form feed); null bytes are rejected, including at boundaries. There is no automatic application-level limit on numeric-string size.

## Date Ranges

`DateRangeFilter` accepts `DateTimeInterface` or absolute ISO-like date strings. It captures independent native timezone data and copies date-time bounds at construction. `getTimezone()` returns an independent `DateTimeZone`; changing either the supplied or returned timezone object does not change the filter. `withTimezone()` changes interpretation of local strings without changing the bound instants. The filter preserves microsecond precision and does not accept relative expressions such as `tomorrow` or silently normalized impossible dates.

```php
$range = new DateRangeFilter(
    '2026-01-01 00:00:00',
    '2026-12-31 23:59:59',
    timezone: new DateTimeZone('Europe/Copenhagen'),
);
```

## Recursive Filtering

`RecursiveFilter` detects iterable-object cycles and enforces `maxDepth` (default `64`) for nested collection values. Independently, `maxAggregateDepth` (default `64`) bounds successive `IteratorAggregate::getIterator()` wrappers at each collection level, including the root. Exceeding either limit throws `OverflowException`. A zero collection-depth limit still permits a flat source wrapped by aggregates; a zero aggregate-depth limit allows no aggregate wrappers. Identity cycles throw `RuntimeException`.

```php
$filter = new RecursiveFilter($predicate, maxDepth: 32, maxAggregateDepth: 16);
```

## Random Filtering

`RandomFilter` creates a private secure `Random\Randomizer` by default instead of consuming the process-global `mt_rand()` state. An explicitly injected randomizer is a borrowed, stateful collaborator; `getRandomizer()` returns that live stream, and external draws advance it. Configuration copies use a separate engine instance where supported. Custom engines must provide independent clone or serialization behavior if independent state is required; external or static state cannot be isolated by the filter. An engine copy failure throws `LogicException`. Probabilities of 0 and 1 do not consume random state.

## Validation

Invalid configuration is rejected early with `InvalidArgumentException` where safe execution would otherwise be impossible. This includes invalid numeric/date ranges, regular expressions, `filter_var()` IDs/options, probabilities, percentages, and typed class/string/key lists.

## Ownership and security boundaries

The `with*` methods do not mutate the receiver's configuration. Validated string/key lists own their scalar entries; arbitrary configuration lists own their top-level slots while retaining nested values and object identities. Native filter options own the top-level options and native option/callback-array slots. Iterables, mutable objects, resources, custom predicates and callback target state are borrowed collaborators, not deep snapshots. A one-shot source is not automatically replayable except when explicitly wrapped (as by `MergingFilter`).

Predicates are selection mechanisms, not authorization or sanitization boundaries. A negative predicate negates any false result, including an unsupported input type. Filesystem predicates may access registered stream wrappers or reveal existence; restrict paths, schemes and authorization separately. The extension predicate rejects paths containing null bytes. A filename extension does not establish MIME type or safe content. Regular expressions should come from trusted configuration and have appropriate PCRE resource limits; a runtime PCRE matching failure is not a successful match. Sanitizing native filters do not return transformed values through this predicate API and do not establish input validity. Native failures are rejected, including failures replaced by a configured `default`. Boolean validation accepts legitimate false values. A native callback rejects an input only when its actual return value is strictly false; null, empty arrays and false values inside returned data are not themselves rejections. In array mode, each input leaf must pass independently. The filter traverses input arrays without writing through caller-owned PHP references or producing a transformed result; cycles are rejected. Shared acyclic subarrays can be visited multiple times. Native callback argument coercion and depth-first input traversal order are retained. Side effects performed by user-provided callbacks or Stringable objects themselves remain the caller's responsibility. Treat executable callbacks, class names that invoke autoloaders, and Stringable/reflective collaborators as trusted code. Bound untrusted collection sizes and input lengths at the application boundary.

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
composer benchmark
```

CI validates Composer metadata, lints PHP sources, tests and benchmarks, and runs Pest on PHP 8.4 and 8.5 in normal, seeded random and reverse order. PHPBench subjects are smoke-tested on PHP 8.4. The Benchmarks workflow runs full samples on both versions when benchmark definitions, audited implementations, or dependency constraints change, or on manual dispatch. It uses the same benchmark definitions and locked dependencies for the candidate and a configurable baseline (the default branch by default). Compare like-for-like environments, dependency versions, input sizes, warm and cold paths; smoke runs are not performance evidence. The mutation-audit workflow checks test sensitivity and can be run manually; it also runs when its workflow definition, dependencies, or selected high-risk regression tests change. The Iterator Integration workflow runs Pest against the pinned source revision of the minimum supported componenta/iterator release (1.1.2), or a manually selected compatible revision, without changing the published dependency constraints.
