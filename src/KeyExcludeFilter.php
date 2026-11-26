<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose key is NOT in the excluded keys array.
 */
final class KeyExcludeFilter extends AbstractFilter
{
    private readonly array $excludedKeys;
    /** @var array<string, true> */
    private readonly array $index;

    public function __construct(
        array $excludedKeys,
        iterable $iterable = []
    ) {
        $this->excludedKeys = KeyList::normalize($excludedKeys, 'excludedKeys');
        $index = [];

        foreach ($this->excludedKeys as $value) {
            $index[KeyList::indexKey($value)] = true;
        }

        $this->index = $index;
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
        return !isset($this->index[KeyList::indexKey($key)]);
    }
}
