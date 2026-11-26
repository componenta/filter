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
    private readonly \DateTimeImmutable $minDate;
    private readonly \DateTimeImmutable $maxDate;
    private readonly \DateTimeZone $timezone;

    public function __construct(
        \DateTimeInterface|string $minDate,
        \DateTimeInterface|string $maxDate,
        iterable $iterable = [],
        ?\DateTimeZone $timezone = null,
    ) {
        // DateTimeZone is mutable through its public constructor and can be
        // subclassed. Capture the native zone, not the caller-owned object
        // (nor any user-defined clone or timezone method).
        $this->timezone = $timezone === null
            ? new \DateTimeZone(date_default_timezone_get())
            : (new \DateTimeImmutable('@0'))->setTimezone($timezone)->getTimezone();

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
        return clone $this->timezone;
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

        $value = trim($value, " \t\n\r\v");

        $matches = self::matchAbsoluteDate($value);

        if ($matches === null) {
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

    /**
     * Parses the fixed ASCII date/time grammar without depending on PCRE's
     * application-configured resource limits. Calendar validity is checked
     * separately by DateTimeImmutable.
     *
     * @return array{date: string, time: ?string, fraction: ?string, offset: ?string}|null
     */
    private static function matchAbsoluteDate(string $value): ?array
    {
        $length = strlen($value);

        if ($length < 10
            || $value[4] !== '-'
            || $value[7] !== '-'
            || strspn($value, '0123456789', 0, 4) !== 4
            || strspn($value, '0123456789', 5, 2) !== 2
            || strspn($value, '0123456789', 8, 2) !== 2
        ) {
            return null;
        }

        $date = substr($value, 0, 10);
        $time = $fraction = $offset = null;

        if ($length === 10) {
            return compact('date', 'time', 'fraction', 'offset');
        }

        if ($length < 19
            || ($value[10] !== 'T' && $value[10] !== ' ')
            || $value[13] !== ':'
            || $value[16] !== ':'
            || strspn($value, '0123456789', 11, 2) !== 2
            || strspn($value, '0123456789', 14, 2) !== 2
            || strspn($value, '0123456789', 17, 2) !== 2
        ) {
            return null;
        }

        $time = substr($value, 11, 8);
        $position = 19;

        if ($position < $length && $value[$position] === '.') {
            $fractionLength = strspn($value, '0123456789', $position + 1);

            if ($fractionLength < 1 || $fractionLength > 6) {
                return null;
            }

            $fraction = substr($value, $position, $fractionLength + 1);
            $position += $fractionLength + 1;
        }

        if ($position === $length) {
            return compact('date', 'time', 'fraction', 'offset');
        }

        $offset = substr($value, $position);

        if ($offset === 'Z') {
            return compact('date', 'time', 'fraction', 'offset');
        }

        if (strlen($offset) !== 6
            || ($offset[0] !== '+' && $offset[0] !== '-')
            || $offset[3] !== ':'
            || strspn($offset, '0123456789', 1, 2) !== 2
            || strspn($offset, '0123456789', 4, 2) !== 2
            || (int) substr($offset, 1, 2) > 23
            || (int) substr($offset, 4, 2) > 59
        ) {
            return null;
        }

        return compact('date', 'time', 'fraction', 'offset');
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
        $timestampComparison = $left->getTimestamp() <=> $right->getTimestamp();

        if ($timestampComparison !== 0) {
            return $timestampComparison;
        }

        return (int) $left->format('u') <=> (int) $right->format('u');
    }
}
