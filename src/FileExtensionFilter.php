<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts files with allowed extensions.
 */
final class FileExtensionFilter extends AbstractFilter
{
    private readonly array $allowedExtensions;
    /** @var array<string, true>|null */
    private ?array $index = null;
    private bool $compared = false;

    public function __construct(
        array $allowedExtensions,
        private readonly bool $caseSensitive = false,
        iterable $iterable = []
    ) {
        $this->allowedExtensions = StringList::normalize($allowedExtensions, 'allowedExtensions');
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

        if ($path === null || str_contains($path, "\0")) {
            return false;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === '') {
            return false;
        }

        if ($this->index === null) {
            if (!$this->compared) {
                $this->compared = true;

                if ($this->caseSensitive) {
                    return in_array($extension, $this->allowedExtensions, true);
                }

                foreach ($this->allowedExtensions as $candidate) {
                    if (strcasecmp($candidate, $extension) === 0) {
                        return true;
                    }
                }

                return false;
            }

            $index = [];

            foreach ($this->allowedExtensions as $candidate) {
                $index['s:' . ($this->caseSensitive ? $candidate : strtolower($candidate))] = true;
            }

            $this->index = $index;
        }

        return isset($this->index['s:' . ($this->caseSensitive ? $extension : strtolower($extension))]);
    }
}
