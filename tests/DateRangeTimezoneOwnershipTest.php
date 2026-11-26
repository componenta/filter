<?php

declare(strict_types=1);

use Componenta\Filter\DateRangeFilter;

it('captures the supplied timezone and does not expose mutable timezone state', function (): void {
    $timezone = new DateTimeZone('Asia/Tokyo');
    $filter = new DateRangeFilter('2026-06-01 12:00:00', '2026-06-01 12:00:00', timezone: $timezone);
    $expected = (new DateTimeImmutable('2026-06-01T03:00:00Z'))->getTimestamp();

    expect($filter->getMinTimestamp())->toBe($expected)
        ->and($filter->getMaxTimestamp())->toBe($expected)
        ->and($filter->accept('2026-06-01 12:00:00'))->toBeTrue();

    $timezone->__construct('UTC');
    $exposed = $filter->getTimezone();
    $exposed->__construct('America/New_York');

    expect($filter->getTimezone()->getName())->toBe('Asia/Tokyo')
        ->and($filter->getMinTimestamp())->toBe($expected)
        ->and($filter->getMaxTimestamp())->toBe($expected)
        ->and($filter->accept('2026-06-01 12:00:00'))->toBeTrue()
        ->and($filter->accept('2026-06-01 03:00:00'))->toBeFalse()
        ->and($filter->accept('2026-06-01T03:00:00Z'))->toBeTrue()
        ->and($filter->withIterable(['2026-06-01 12:00:00'])->toArray())->toBe(['2026-06-01 12:00:00']);
});

it('keeps bound instants fixed when replacing the captured timezone', function (): void {
    $original = new DateRangeFilter(
        '2026-06-01T03:00:00Z',
        '2026-06-01T03:00:00Z',
        timezone: new DateTimeZone('UTC'),
    );
    $timezone = new DateTimeZone('+09:00');
    $replacement = $original->withTimezone($timezone);
    $timezone->__construct('UTC');

    expect($original->accept('2026-06-01 03:00:00'))->toBeTrue()
        ->and($original->accept('2026-06-01 12:00:00'))->toBeFalse()
        ->and($replacement->accept('2026-06-01 12:00:00'))->toBeTrue()
        ->and($replacement->getMinTimestamp())->toBe($original->getMinTimestamp())
        ->and($replacement->getMaxTimestamp())->toBe($original->getMaxTimestamp());

    $returned = $replacement->getTimezone();
    $returned->__construct('UTC');

    expect($replacement->getTimezone()->getName())->toBe('+09:00')
        ->and($replacement->withMinDate('2026-06-01 12:00:00')->getMinTimestamp())->toBe($original->getMinTimestamp());
});

it('captures native timezone data without retaining caller-defined timezone behavior', function (): void {
    $timezone = new class('Europe/Copenhagen') extends DateTimeZone {
        public function __clone(): void
        {
            throw new LogicException('Must not execute a user-defined clone');
        }

        public function getName(): string
        {
            throw new LogicException('Must use the native timezone name');
        }

        public function getTransitions(int $timestampBegin = PHP_INT_MIN, int $timestampEnd = PHP_INT_MAX): array|false
        {
            throw new LogicException('Must use the native transition table');
        }
    };

    $filter = new DateRangeFilter(
        '2026-10-25T01:00:00+02:00',
        '2026-10-25T04:00:00+01:00',
        timezone: $timezone,
    );

    expect($filter->getTimezone()->getName())->toBe('Europe/Copenhagen')
        ->and($filter->accept('2026-10-25 02:30:00'))->toBeFalse()
        ->and($filter->accept('2026-10-25 03:30:00'))->toBeTrue();
});
