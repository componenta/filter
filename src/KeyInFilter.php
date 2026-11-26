<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose key is in the allowed keys array.
 */
final class KeyInFilter extends AbstractFilter
{
    private readonly array $allowedKeys;

    public function __construct(
        array $allowedKeys,
        iterable $iterable = []
    ) {
        $this->allowedKeys = KeyList::normalize($allowedKeys, 'allowedKeys');
        parent::__construct($iterable);
    }

    public function withAllowedKeys(array $allowedKeys): static
    {
        return new self($allowedKeys, $this->iterable);
    }

    public function getAllowedKeys(): array
    {
        return $this->allowedKeys;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return in_array($key, $this->allowedKeys, true);
    }
}
