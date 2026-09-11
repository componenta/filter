<?php

declare(strict_types=1);

use Componenta\Filter\DateRangeFilter;

it('preserves exact timestamps across an ambiguous DST hour', function (): void {
    $previousTimezone = date_default_timezone_get();
    date_default_timezone_set('Europe/Copenhagen');

    try {
        $firstOccurrence = 1792888200;  // 2026-10-25 02:30:00 +02:00
        $secondOccurrence = 1792891800; // 2026-10-25 02:30:00 +01:00

        $filter = DateRangeFilter::fromTimestamps($firstOccurrence, $secondOccurrence);

        expect($filter->getMinTimestamp())->toBe($firstOccurrence)
            ->and($filter->getMaxTimestamp())->toBe($secondOccurrence);
    } finally {
        date_default_timezone_set($previousTimezone);
    }
});

it('captures its timezone instead of depending on later global timezone changes', function (): void {
    $previousTimezone = date_default_timezone_get();
    date_default_timezone_set('Europe/Copenhagen');

    try {
        $filter = new DateRangeFilter(
            '2026-06-01 12:00:00',
            '2026-06-01 12:00:00',
        );

        date_default_timezone_set('UTC');

        expect($filter->accept('2026-06-01 12:00:00'))->toBeTrue()
            ->and($filter->getTimezone()->getName())->toBe('Europe/Copenhagen');
    } finally {
        date_default_timezone_set($previousTimezone);
    }
});

it('supports an explicit timezone for local date strings', function (): void {
    $timezone = new DateTimeZone('Asia/Tokyo');
    $filter = new DateRangeFilter(
        '2026-06-01 12:00:00',
        '2026-06-01 12:00:00',
        timezone: $timezone,
    );

    expect($filter->accept('2026-06-01 12:00:00'))->toBeTrue()
        ->and($filter->getTimezone())->toBe($timezone);
});

it('accepts DateTimeInterface values without reparsing through strings', function (): void {
    $filter = new DateRangeFilter(
        new DateTimeImmutable('2026-06-01T10:00:00+00:00'),
        new DateTimeImmutable('2026-06-01T12:00:00+00:00'),
    );

    expect($filter->accept(new DateTimeImmutable('2026-06-01T11:00:00+00:00')))->toBeTrue()
        ->and($filter->accept(new DateTimeImmutable('2026-06-01T13:00:00+00:00')))->toBeFalse();
});

it('rejects relative and impossible date strings', function (string $date): void {
    new DateRangeFilter($date, '2026-12-31');
})->with([
    'relative' => 'tomorrow',
    'impossible calendar date' => '2026-02-30',
])->throws(InvalidArgumentException::class);

it('rejects relative and impossible date values during filtering', function (string $date): void {
    $filter = new DateRangeFilter('2026-01-01', '2026-12-31');

    expect($filter->accept($date))->toBeFalse();
})->with([
    'relative' => 'tomorrow',
    'impossible calendar date' => '2026-02-30',
]);

it('rejects a date range whose minimum is after its maximum', function (): void {
    new DateRangeFilter('2026-12-31', '2026-01-01');
})->throws(InvalidArgumentException::class);

it('rejects reversed timestamp bounds', function (): void {
    DateRangeFilter::fromTimestamps(2, 1);
})->throws(InvalidArgumentException::class);
