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

it('rejects a date range whose minimum is after its maximum', function (): void {
    new DateRangeFilter('2026-12-31', '2026-01-01');
})->throws(InvalidArgumentException::class);

it('rejects reversed timestamp bounds', function (): void {
    DateRangeFilter::fromTimestamps(2, 1);
})->throws(InvalidArgumentException::class);
