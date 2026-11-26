<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value equals any of the allowed strings.
 */
final class StringEqualsAnyFilter extends AbstractFilter
{
    private readonly array $allowed;
    /** @var array<string, true>|null */
    private ?array $index = null;
    private bool $compared = false;

    public function __construct(
        array $allowed,
        private readonly bool $caseSensitive = true,
        iterable $iterable = []
    ) {
        $this->allowed = StringList::normalize($allowed, 'allowed');
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

        if ($this->index === null) {
            if (!$this->compared) {
                $this->compared = true;

                if ($this->caseSensitive) {
                    return in_array($stringValue, $this->allowed, true);
                }

                foreach ($this->allowed as $candidate) {
                    if (strcasecmp($candidate, $stringValue) === 0) {
                        return true;
                    }
                }

                return false;
            }

            $index = [];

            foreach ($this->allowed as $candidate) {
                $index['s:' . ($this->caseSensitive ? $candidate : strtolower($candidate))] = true;
            }

            $this->index = $index;
        }

        return isset($this->index['s:' . ($this->caseSensitive ? $stringValue : strtolower($stringValue))]);
    }
}
