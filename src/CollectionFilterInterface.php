<?php

declare(strict_types=1);

namespace Componenta\Filter;

use Componenta\Arrayable\Arrayable;

/**
 * Transforms or filters an iterable collection.
 */
interface CollectionFilterInterface extends \IteratorAggregate, Arrayable
{
    public function withIterable(iterable $iterable): static;

    public function toArray(bool $preserveKeys = false): array;
}
