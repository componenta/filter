<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts absolute date/time values that fall within the configured range.
 *
 * Local date strings are interpreted in the timezone captured by the filter.
 * Relative date expressions are intentionally unsupported.
 */
final class DateRangeFilter extends AbstractFilter
{
    private int $minTimestamp;
    private int $maxTimestamp;
    private readonly \DateTimeZone $timezone;

    public function __construct(
        \DateTimeInterface|string $minDate,
        \DateTimeInterface|string $maxDate,
        iterable $iterable = [],
        ?\DateTimeZone $timezone = null,
    ) {
        $this->timezone = $timezone ?? new \DateTimeZone(date_default_timezone_get());

        $min = self::parseAbsoluteDate($minDate, $this->timezone);
        $max = self::parseAbsoluteDate($maxDate, $this->timezone);

        if ($min === null) {
            throw new \InvalidArgumentException('Invalid absolute minimum date');
        }

        if ($max === null) {
            throw new \InvalidArgumentException('Invalid absolute maximum date');
        }

        if ($min > $max) {
            throw new \InvalidArgumentException('Minimum date must not be after maximum date');
        }

        $this->minTimestamp = $min;
        $this->maxTimestamp = $max;
        parent::__construct($iterable);
    }

    public static function fromTimestamps(
        int $minTimestamp,
        int $maxTimestamp,
        iterable $iterable = [],
        ?\DateTimeZone $timezone = null,
    ): self {
        return new self(
            new \DateTimeImmutable(sprintf('@%d', $minTimestamp)),
            new \DateTimeImmutable(sprintf('@%d', $maxTimestamp)),
            $iterable,
            $timezone,
        );
    }

    public function withMinDate(\DateTimeInterface|string $minDate): static
    {
        return new self($minDate, new \DateTimeImmutable(sprintf('@%d', $this->maxTimestamp)), $this->iterable, $this->timezone);
    }

    public function withMaxDate(\DateTimeInterface|string $maxDate): static
    {
        return new self(new \DateTimeImmutable(sprintf('@%d', $this->minTimestamp)), $maxDate, $this->iterable, $this->timezone);
    }

    public function withTimezone(\DateTimeZone $timezone): static
    {
        return self::fromTimestamps(
            $this->minTimestamp,
            $this->maxTimestamp,
            $this->iterable,
            $timezone,
        );
    }

    public function getMinTimestamp(): int
    {
        return $this->minTimestamp;
    }

    public function getMaxTimestamp(): int
    {
        return $this->maxTimestamp;
    }

    public function getTimezone(): \DateTimeZone
    {
        return $this->timezone;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof \DateTimeInterface && !is_string($value)) {
            return false;
        }

        $timestamp = self::parseAbsoluteDate($value, $this->timezone);

        return $timestamp !== null
            && $timestamp >= $this->minTimestamp
            && $timestamp <= $this->maxTimestamp;
    }

    private static function parseAbsoluteDate(
        \DateTimeInterface|string $value,
        \DateTimeZone $timezone,
    ): ?int {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $value = trim($value);

        if ($value === '' || preg_match(
            '/^\d{4}-\d{2}-\d{2}(?:(?:[ T])\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})?)?$/D',
            $value,
        ) !== 1) {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($value, $timezone);
        } catch (\Exception) {
            return null;
        }

        $errors = \DateTimeImmutable::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $date->getTimestamp();
    }
}
