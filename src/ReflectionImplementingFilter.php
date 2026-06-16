<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts ReflectionClass elements that implement the specified interface.
 */
final class ReflectionImplementingFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $interfaceName,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withInterfaceName(string $interfaceName): static
    {
        return new self($interfaceName, $this->iterable);
    }

    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof \ReflectionClass) {
            return false;
        }

        return $value->implementsInterface($this->interfaceName);
    }
}
