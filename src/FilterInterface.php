<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * A predicate-backed collection filter.
 *
 * This is the intersection of PredicateInterface and CollectionFilterInterface.
 * Collection operators whose behavior depends on the whole iterable should
 * implement CollectionFilterInterface directly instead.
 */
interface FilterInterface extends PredicateInterface, CollectionFilterInterface
{
}
