<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Contract for immutable predicate composition.
 */
interface FilterableInterface
{
    public function withFilter(PredicateInterface $filter, bool $prepend = false): static;

    public function hasFilter(PredicateInterface $filter): bool;

    public function withoutFilter(PredicateInterface $filter): static;
}
