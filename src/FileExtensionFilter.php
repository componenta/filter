<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts files with allowed extensions.
 */
final class FileExtensionFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $allowedExtensions,
        private readonly bool $caseSensitive = false,
        iterable $iterable = []
    ) {
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
        $extension = pathinfo((string) $value, PATHINFO_EXTENSION);

        if ($extension === '') {
            return false;
        }

        if ($this->caseSensitive) {
            return in_array($extension, $this->allowedExtensions, true);
        }

        $extension = strtolower($extension);
        $allowed = array_map('strtolower', $this->allowedExtensions);

        return in_array($extension, $allowed, true);
    }
}
