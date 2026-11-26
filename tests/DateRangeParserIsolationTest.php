<?php

declare(strict_types=1);

use Componenta\Filter\DateRangeFilter;

it('does not depend on PCRE limits for absolute date parsing', function (): void {
    $timezone = new DateTimeZone('UTC');
    $range = new DateRangeFilter('2026-01-01', '2026-12-31', timezone: $timezone);
    $oldBacktrack = ini_get('pcre.backtrack_limit');
    $oldJit = ini_get('pcre.jit');

    try {
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '1');

        foreach ([
            '2026-01-01',
            '2026-06-01 12:00:00',
            '2026-06-01T12:00:00.1',
            '2026-06-01T12:00:00.123456Z',
            '2026-06-01T12:00:00+01:30',
            '2026-06-01T12:00:00-01:30',
        ] as $value) {
            expect($range->accept($value))->toBeTrue()
                ->and((new DateRangeFilter('2026-01-01', '2026-12-31', timezone: $timezone))->accept($value))->toBeTrue();
        }

        foreach ([
            '2026-02-30',
            '2026-01-01Z',
            '2026-01-01T12:00',
            '2026-01-01T12:00:00.',
            '2026-01-01T12:00:00.1234567Z',
            '2026-01-01T12:00:00+24:00',
            '2026-01-01T12:00:00+01:60',
            '2026-01-01T12:00:00+0100',
            '2026-01-01T12:00:00z',
            "2026-01-01\0",
        ] as $invalid) {
            expect($range->accept($invalid))->toBeFalse();
        }
    } finally {
        ini_set('pcre.backtrack_limit', (string) $oldBacktrack);
        ini_set('pcre.jit', (string) $oldJit);
    }
});
