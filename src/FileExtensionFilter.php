<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts files with allowed extensions.
 */
final class FileExtensionFilter extends AbstractFilter
{
    private readonly array $allowedExtensions;
    /** @var array<string, true> */
    private readonly array $index;

    public function __construct(
        array $allowedExtensions,
        private readonly bool $caseSensitive = false,
        iterable $iterable = []
    ) {
        $this->allowedExtensions = StringList::normalize($allowedExtensions, 'allowedExtensions');
        $index = [];

        foreach ($this->allowedExtensions as $value) {
            $index['s:' . ($caseSensitive ? $value : strtolower($value))] = true;
        }

        $this->index = $index;
        parent::__construct($iterable);
    }

    public function withAllowedExtensions(array $allowedExtensions): static
    {
        return new self($allowedExtensions, $this->caseSensitive, $this->iterable);
    }

    public function withCaseSensitive(bool $caseSensitive): static
    {
        return new self($this->allowedExtensions, $caseSensitive, $this->iterable);
    }

    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $path = StringValue::from($value);

        if ($path === null) {
            return false;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === '') {
            return false;
        }

        return isset($this->index['s:' . ($this->caseSensitive ? $extension : strtolower($extension))]);
    }
}
