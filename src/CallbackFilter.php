<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements based on a user-provided callback.
 */
final class CallbackFilter extends AbstractFilter
{
    /** @var callable(mixed, string|int|null): bool */
    private $callback;

    /**
     * @param callable(mixed, string|int|null): bool $callback
     */
    public function __construct(
        callable $callback,
        iterable $iterable = []
    ) {
        $this->callback = $callback;
        parent::__construct($iterable);
    }

    /**
     * @param callable(mixed, string|int|null): bool $callback
     */
    public function withCallback(callable $callback): static
    {
        return new self($callback, $this->iterable);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return ($this->callback)($value, $key);
    }
}
