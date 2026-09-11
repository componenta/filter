<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts absolute date/time values that fall within the configured range.
 *
 * Local date strings are interpreted in the timezone captured by the filter.
 * Relative date expressions are intentionally unsupported. Comparisons retain
 * microsecond precision. Nonexistent and ambiguous local wall times caused by
 * timezone transitions are rejected unless an explicit offset disambiguates
 * the instant. Date-only strings represent strict local midnight.
 */
final class DateRangeFilter extends AbstractFilter
{
    private const string ABSOLUTE_DATE_PATTERN = '/^(?<date>\d{4}-\d{2}-\d{2})(?:(?:[ T])(?<time>\d{2}:\d{2}:\d{2})(?<fraction>\.\d{1,6})?(?<offset>Z|[+-]\d{2}:\d{2})?)?$/D';

    private readonly \DateTimeImmutable $minDate;
    private readonly \DateTimeImmutable $maxDate;
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

        if (self::compareInstants($min, $max) > 0) {
            throw new \InvalidArgumentException('Minimum date must not be after maximum date');
        }

        $this->minDate = $min;
        $this->maxDate = $max;
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
        return new self($minDate, $this->maxDate, $this->iterable, $this->timezone);
    }

    public function withMaxDate(\DateTimeInterface|string $maxDate): static
    {
        return new self($this->minDate, $maxDate, $this->iterable, $this->timezone);
    }

    public function withTimezone(\DateTimeZone $timezone): static
    {
        return new self($this->minDate, $this->maxDate, $this->iterable, $timezone);
    }

    public function getMinTimestamp(): int
    {
        return $this->minDate->getTimestamp();
    }

    public function getMaxTimestamp(): int
    {
        return $this->maxDate->getTimestamp();
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

        $date = self::parseAbsoluteDate($value, $this->timezone);

        return $date !== null
            && self::compareInstants($date, $this->minDate) >= 0
            && self::compareInstants($date, $this->maxDate) <= 0;
    }

    private static function parseAbsoluteDate(
        \DateTimeInterface|string $value,
        \DateTimeZone $timezone,
    ): ?\DateTimeImmutable {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        $value = trim($value);

        if ($value === '' || preg_match(
            self::ABSOLUTE_DATE_PATTERN,
            $value,
            $matches,
            PREG_UNMATCHED_AS_NULL,
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

        if (($matches['offset'] ?? null) === null) {
            if (!self::matchesLocalWallTime($date, $matches)
                || self::isAmbiguousLocalWallTime($date, $matches, $timezone)
            ) {
                return null;
            }
        }

        return $date;
    }

    /** @param array<string, string|null> $matches */
    private static function matchesLocalWallTime(
        \DateTimeImmutable $date,
        array $matches,
    ): bool {
        $time = $matches['time'] ?? '00:00:00';
        $fraction = $matches['fraction'] ?? null;

        if ($fraction === null) {
            return $date->format('Y-m-d H:i:s') === sprintf(
                '%s %s',
                $matches['date'],
                $time,
            );
        }

        return $date->format('Y-m-d H:i:s.u') === sprintf(
            '%s %s.%s',
            $matches['date'],
            $time,
            str_pad(substr($fraction, 1), 6, '0'),
        );
    }

    /** @param array<string, string|null> $matches */
    private static function isAmbiguousLocalWallTime(
        \DateTimeImmutable $date,
        array $matches,
        \DateTimeZone $timezone,
    ): bool {
        $time = $matches['time'] ?? '00:00:00';
        $transitions = $timezone->getTransitions(
            $date->getTimestamp() - 172800,
            $date->getTimestamp() + 172800,
        );

        if ($transitions === false || count($transitions) < 2) {
            return false;
        }

        $wallTime = \DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            sprintf('%s %s', $matches['date'], $time),
            new \DateTimeZone('UTC'),
        );

        if ($wallTime === false) {
            return false;
        }

        $wallTimestamp = $wallTime->getTimestamp();
        $previousOffset = $transitions[0]['offset'];

        for ($i = 1, $count = count($transitions); $i < $count; $i++) {
            $transition = $transitions[$i];
            $nextOffset = $transition['offset'];

            if ($nextOffset < $previousOffset) {
                $repeatedStart = $transition['ts'] + $nextOffset;
                $repeatedEnd = $transition['ts'] + $previousOffset;

                if ($wallTimestamp >= $repeatedStart && $wallTimestamp < $repeatedEnd) {
                    return true;
                }
            }

            $previousOffset = $nextOffset;
        }

        return false;
    }

    /** @return -1|0|1 */
    private static function compareInstants(
        \DateTimeInterface $left,
        \DateTimeInterface $right,
    ): int {
        return NumericValueComparator::compare(
            $left->format('U.u'),
            $right->format('U.u'),
        ) ?? 0;
    }
}
