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

        expect($filter->accept(new DateTimeImmutable('@' . ($firstOccurrence - 1))))->toBeFalse()
            ->and($filter->accept(new DateTimeImmutable('@' . $firstOccurrence)))->toBeTrue()
            ->and($filter->accept(new DateTimeImmutable('@' . $secondOccurrence)))->toBeTrue()
            ->and($filter->accept(new DateTimeImmutable('@' . ($secondOccurrence + 1))))->toBeFalse();
    } finally {
        date_default_timezone_set($previousTimezone);
    }
});

it('compares fractional seconds exactly', function (): void {
    $filter = new DateRangeFilter(
        '2026-06-01T12:00:00.200000+00:00',
        '2026-06-01T12:00:00.300000+00:00',
    );

    expect($filter->accept('2026-06-01T12:00:00.100000+00:00'))->toBeFalse()
        ->and($filter->accept('2026-06-01T12:00:00.250000+00:00'))->toBeTrue()
        ->and($filter->accept('2026-06-01T12:00:00.400000+00:00'))->toBeFalse();
});

it('orders fractional seconds correctly before the Unix epoch', function (): void {
    $filter = new DateRangeFilter(
        '1969-12-31T23:59:59.200000+00:00',
        '1969-12-31T23:59:59.800000+00:00',
    );

    expect($filter->accept('1969-12-31T23:59:59.100000+00:00'))->toBeFalse()
        ->and($filter->accept('1969-12-31T23:59:59.200000+00:00'))->toBeTrue()
        ->and($filter->accept('1969-12-31T23:59:59.500000+00:00'))->toBeTrue()
        ->and($filter->accept('1969-12-31T23:59:59.800000+00:00'))->toBeTrue()
        ->and($filter->accept('1969-12-31T23:59:59.900000+00:00'))->toBeFalse();
});

it('binds and filters an iterable through the date predicate', function (): void {
    $filter = new DateRangeFilter(
        '2026-06-01T12:00:00+00:00',
        '2026-06-01T13:00:00+00:00',
        [
            'before' => '2026-06-01T11:59:59+00:00',
            'inside' => '2026-06-01T12:30:00+00:00',
            'after' => '2026-06-01T13:00:01+00:00',
        ],
    );

    expect($filter->toArray(preserveKeys: true))->toBe([
        'inside' => '2026-06-01T12:30:00+00:00',
    ]);
});

it('trims surrounding whitespace from absolute date strings', function (): void {
    $filter = new DateRangeFilter(
        ' 2026-06-01T12:00:00+00:00 ',
        "\t2026-06-01T13:00:00+00:00\n",
    );

    expect($filter->accept(' 2026-06-01T12:30:00+00:00 '))->toBeTrue();
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

        expect($filter->accept('2026-06-01 12:00:00'))->toBeTrue();
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

    expect($filter->accept('2026-06-01 12:00:00'))->toBeTrue();
});

it('supports fixed-offset timezones without transition tables', function (): void {
    $timezone = new DateTimeZone('+02:00');
    $filter = new DateRangeFilter(
        '2026-06-01 12:00:00',
        '2026-06-01 13:00:00',
        timezone: $timezone,
    );

    expect($filter->accept('2026-06-01 12:30:00'))->toBeTrue();
});

it('rejects local times normalized through a DST gap', function (): void {
    $timezone = new DateTimeZone('Europe/Copenhagen');

    expect(fn() => new DateRangeFilter(
        '2026-03-29 02:30:00',
        '2026-03-29 04:00:00',
        timezone: $timezone,
    ))->toThrow(InvalidArgumentException::class);

    $filter = new DateRangeFilter(
        '2026-03-29 01:00:00',
        '2026-03-29 04:00:00',
        timezone: $timezone,
    );

    expect($filter->accept('2026-03-29 02:30:00'))->toBeFalse();
});

it('rejects ambiguous local times in a DST overlap unless an offset is explicit', function (): void {
    $timezone = new DateTimeZone('Europe/Copenhagen');

    expect(fn() => new DateRangeFilter(
        '2026-10-25 02:30:00',
        '2026-10-25 04:00:00',
        timezone: $timezone,
    ))->toThrow(InvalidArgumentException::class);

    $filter = new DateRangeFilter(
        '2026-10-25T02:30:00+02:00',
        '2026-10-25T02:30:00+01:00',
        timezone: $timezone,
    );

    expect($filter->accept('2026-10-25 02:30:00'))->toBeFalse()
        ->and($filter->accept('2026-10-25T02:30:00+02:00'))->toBeTrue()
        ->and($filter->accept('2026-10-25T02:30:00+01:00'))->toBeTrue();
});

it('keeps the boundaries around a DST overlap unambiguous', function (): void {
    $timezone = new DateTimeZone('Europe/Copenhagen');
    $filter = new DateRangeFilter(
        '2026-10-25T00:00:00+02:00',
        '2026-10-25T04:00:00+01:00',
        timezone: $timezone,
    );

    expect($filter->accept('2026-10-25 01:30:00'))->toBeTrue()
        ->and($filter->accept('2026-10-25 02:30:00'))->toBeFalse()
        ->and($filter->accept('2026-10-25 03:00:00'))->toBeTrue()
        ->and($filter->accept('2026-10-25 03:30:00'))->toBeTrue();
});

it('treats date-only input as strict local midnight across timezone transitions', function (): void {
    expect(fn() => new DateRangeFilter(
        '2023-04-28',
        '2023-04-29',
        timezone: new DateTimeZone('Africa/Cairo'),
    ))->toThrow(InvalidArgumentException::class);

    expect(fn() => new DateRangeFilter(
        '2020-11-01',
        '2020-11-02',
        timezone: new DateTimeZone('America/Havana'),
    ))->toThrow(InvalidArgumentException::class);
});

it('accepts DateTimeInterface values without reparsing through strings', function (): void {
    $filter = new DateRangeFilter(
        new DateTimeImmutable('2026-06-01T10:00:00+00:00'),
        new DateTimeImmutable('2026-06-01T12:00:00+00:00'),
    );

    expect($filter->accept(new DateTimeImmutable('2026-06-01T11:00:00+00:00')))->toBeTrue()
        ->and($filter->accept(new DateTimeImmutable('2026-06-01T13:00:00+00:00')))->toBeFalse();
});

it('rejects numeric UTC offsets outside RFC 3339 bounds', function (): void {
    $valid = new DateRangeFilter(
        '2026-01-01T00:00:00+23:59',
        '2026-01-01T01:00:00+23:59',
    );

    expect($valid->accept('2026-01-01T00:30:00+23:59'))->toBeTrue()
        ->and($valid->accept('2026-01-01T00:30:00+24:00'))->toBeFalse()
        ->and($valid->accept('2026-01-01T00:30:00-24:00'))->toBeFalse();

    expect(fn() => new DateRangeFilter(
        '2026-01-01T00:00:00+24:00',
        '2026-01-01T01:00:00+24:00',
    ))->toThrow(InvalidArgumentException::class);
});

it('rejects relative and impossible date strings', function (string $date): void {
    new DateRangeFilter($date, '2026-12-31');
})->with([
    'relative' => 'tomorrow',
    'impossible calendar date' => '2026-02-30',
    'impossible calendar date with explicit offset' => '2026-02-30T12:00:00+00:00',
    'normalized clock time with explicit offset' => '2026-01-01T25:00:00+00:00',
])->throws(InvalidArgumentException::class);

it('rejects relative and impossible date values during filtering', function (string $date): void {
    $filter = new DateRangeFilter('2026-01-01', '2026-12-31');

    expect($filter->accept($date))->toBeFalse();
})->with([
    'relative' => 'tomorrow',
    'impossible calendar date' => '2026-02-30',
    'impossible calendar date with explicit offset' => '2026-02-30T12:00:00+00:00',
    'normalized clock time with explicit offset' => '2026-01-01T25:00:00+00:00',
]);

it('rejects a date range whose minimum is after its maximum', function (): void {
    new DateRangeFilter('2026-12-31', '2026-01-01');
})->throws(InvalidArgumentException::class);

it('rejects reversed timestamp bounds', function (): void {
    DateRangeFilter::fromTimestamps(2, 1);
})->throws(InvalidArgumentException::class);
