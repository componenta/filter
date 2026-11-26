<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose key is NOT in the excluded keys array.
 */
final class KeyExcludeFilter extends AbstractFilter
{
    private readonly array $excludedKeys;

    public function __construct(
        array $excludedKeys,
        iterable $iterable = []
    ) {
        $this->excludedKeys = KeyList::normalize($excludedKeys, 'excludedKeys');
        parent::__construct($iterable);
    }

    public function withExcludedKeys(array $excludedKeys): static
    {
        return new self($excludedKeys, $this->iterable);
    }

    public function getExcludedKeys(): array
    {
        return $this->excludedKeys;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return !in_array($key, $this->excludedKeys, true);
    }
}
