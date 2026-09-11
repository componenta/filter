<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Evaluates a single value independently from any iterable source.
 */
interface PredicateInterface
{
    public function accept(mixed $value, string|int|null $key = null): bool;
}
