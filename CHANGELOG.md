# Changelog

## [2.1.0] - 2026-11-26

### Added

- Independent `RecursiveFilter` aggregate-wrapper depth configuration: the optional `maxAggregateDepth` constructor argument, `withMaxAggregateDepth()` and `getMaxAggregateDepth()`.
- PHPBench scenarios for predicates, membership checks and collection traversal; reproducible numerical regression fixtures, mutation auditing and iterator integration checks.

### Fixed

- Exact percentage truncation, including adjacent floating-point values and large collection sizes.
- Exact numeric comparisons and divisibility across large integers, scientific notation and decimals; consistent parsing under restricted PCRE and PHP INI settings. Float tolerance no longer masks conversion errors in an exact divisor.
- `FilterVarFilter` rejects native failures even with configured defaults, preserves valid boolean and callback results, and validates nested arrays without overwriting caller-owned reference slots. Callback order and native coercion are retained.
- `DateRangeFilter` snapshots timezone state, rejects ambiguous or invalid local dates and keeps microsecond precision.
- Immutable configuration copies detach validated list slots, and callback configurations no longer depend on subsequent changes to the callable array.
- `MergingFilter` opens and rewinds sources lazily, shares an independent-cursor replay cache and preserves initialization failures.
- Recursive aggregate traversal has bounded depth and cycle detection. Filename extension predicates reject embedded NUL bytes.
- Array materialization normalizes null keys to empty strings without PHP 8.5 deprecations.

### Performance

- Indexed scalar/object uniqueness and stable membership checks replace repeated linear scans where semantics permit.
- Prepared numeric bounds and divisors, integer fast paths, lazy string/extension indexes and bounded-error fast percentage arithmetic.
- Detached flat scalar native validation is batched for flags-only numeric and boolean filters; complex inputs use the nonmutating walker.

### Compatibility and behavioral corrections

- PHP 8.4 and 8.5 remain supported. The minimum `componenta/iterator` version is now 1.1.2 to ensure replay integrity and interrupted Fiber-read safety.
- The new aggregate-wrapper depth limit defaults to 64; deeper intentional recursive aggregate chains can be enabled with `maxAggregateDepth`. The replay source used by `MergingFilter` also rejects chains exceeding 64 wrappers.
- Materialization with preserved keys copies top-level value slots; it does not promise propagation of source references. Nested values and object identities are not deep-copied.
- Invalid paths, masked native failures and imprecise numerical matches may now be rejected where an earlier implementation accepted them.
- Existing stateful collaborators (custom predicates, mutable objects and injected random engines) remain borrowed, as described in the README.
- Safe nested native array validation and exact percentage arithmetic have some additional overhead on small inputs. Performance results depend on the workload.

### Verification

- PHP 8.4: 475 passed, 3 version-specific skips, 11,643 assertions.
- PHP 8.5: 478 passed, 11,656 assertions.
- All three Pest execution orders; 39 PHPBench subjects across 71 parameterized cases per PHP version; mutation score 86.77% (8.4) and 86.72% (8.5), not a claim of complete mutation coverage.
- Audited source revision: `eda0cca196292158aed3cf6a9b699c9cf13e29a5`.

[Compare v2.0.0...v2.1.0](https://github.com/componenta/filter/compare/v2.0.0...v2.1.0)
