<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose date value falls within the specified range.
 */
final class DateRangeFilter extends AbstractFilter
{
    private int $minTimestamp;
    private int $maxTimestamp;

    public function __construct(
        string $minDate,
        string $maxDate,
        iterable $iterable = []
    ) {
        $min = strtotime($minDate);
        $max = strtotime($maxDate);

        if ($min === false) {
            throw new \InvalidArgumentException("Invalid min date format: $minDate");
        }
        if ($max === false) {
            throw new \InvalidArgumentException("Invalid max date format: $maxDate");
        }

        $this->minTimestamp = $min;
        $this->maxTimestamp = $max;
        parent::__construct($iterable);
    }

    public static function fromTimestamps(
        int $minTimestamp,
        int $maxTimestamp,
        iterable $iterable = []
    ): self {
        return new self(
            sprintf('@%d', $minTimestamp),
            sprintf('@%d', $maxTimestamp),
            $iterable,
        );
    }

    public function withMinDate(string $minDate): static
    {
        $min = strtotime($minDate);
        if ($min === false) {
            throw new \InvalidArgumentException("Invalid date: $minDate");
        }

        return self::fromTimestamps($min, $this->maxTimestamp, $this->iterable);
    }

    public function withMaxDate(string $maxDate): static
    {
        $max = strtotime($maxDate);
        if ($max === false) {
            throw new \InvalidArgumentException("Invalid date: $maxDate");
        }

        return self::fromTimestamps($this->minTimestamp, $max, $this->iterable);
    }

    public function getMinTimestamp(): int
    {
        return $this->minTimestamp;
    }

    public function getMaxTimestamp(): int
    {
        return $this->maxTimestamp;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $date = StringValue::from($value);

        if ($date === null) {
            return false;
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return false;
        }

        return $timestamp >= $this->minTimestamp && $timestamp <= $this->maxTimestamp;
    }
}
