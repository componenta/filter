<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose key is in the allowed keys array.
 */
final class KeyInFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $allowedKeys,
        iterable $iterable = []
    ) {
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
