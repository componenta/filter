<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value equals any of the allowed strings.
 */
final class StringEqualsAnyFilter extends AbstractFilter
{
    private readonly array $allowed;
    /** @var array<string, true> */
    private readonly array $index;

    public function __construct(
        array $allowed,
        private readonly bool $caseSensitive = true,
        iterable $iterable = []
    ) {
        $this->allowed = StringList::normalize($allowed, 'allowed');
        $index = [];

        foreach ($this->allowed as $value) {
            $index['s:' . ($caseSensitive ? $value : strtolower($value))] = true;
        }

        $this->index = $index;
        parent::__construct($iterable);
    }

    public function withAllowed(array $allowed): static
    {
        return new self($allowed, $this->caseSensitive, $this->iterable);
    }

    public function withCaseSensitive(bool $caseSensitive): static
    {
        return new self($this->allowed, $caseSensitive, $this->iterable);
    }

    public function getAllowed(): array
    {
        return $this->allowed;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $stringValue = StringValue::from($value);

        if ($stringValue === null) {
            return false;
        }

        return isset($this->index['s:' . ($this->caseSensitive ? $stringValue : strtolower($stringValue))]);
    }
}
