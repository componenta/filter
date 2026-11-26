<?php

declare(strict_types=1);

use Componenta\Filter\DateRangeFilter;
use Componenta\Filter\EvenNumberFilter;
use Componenta\Filter\MultipleOfFilter;
use Componenta\Filter\NumericFilter;
use Componenta\Filter\OddNumberFilter;
use Componenta\Filter\RangeFilter;

it('rejects embedded or boundary null bytes in numeric predicates', function (string $value): void {
    expect((new NumericFilter())->accept($value))->toBeFalse()
        ->and((new EvenNumberFilter())->accept($value))->toBeFalse()
        ->and((new OddNumberFilter())->accept($value))->toBeFalse()
        ->and((new RangeFilter(0, 100))->accept($value))->toBeFalse()
        ->and((new MultipleOfFilter(2))->accept($value))->toBeFalse();
})->with(["42\0", "\0 42", "4\0" . "2", " 42\0 "]);

it('accepts PHP numeric whitespace consistently without accepting other bytes', function (string $space): void {
    $value = $space . '42' . $space;
    expect((new NumericFilter())->accept($value))->toBeTrue()
        ->and((new EvenNumberFilter())->accept($value))->toBeTrue()
        ->and((new OddNumberFilter())->accept($value))->toBeFalse()
        ->and((new RangeFilter(42, 42))->accept($value))->toBeTrue()
        ->and((new MultipleOfFilter(2))->accept($value))->toBeTrue();
})->with([" ", "\t", "\n", "\r", "\v", "\f"]);

it('rejects null bytes in numeric configuration and date strings', function (): void {
    expect(fn() => new RangeFilter("42\0", 100))->toThrow(InvalidArgumentException::class)
        ->and((new DateRangeFilter('2026-01-01', '2026-12-31'))->accept("2026-06-01\0"))->toBeFalse()
        ->and(fn() => new DateRangeFilter("\0" . "2026-01-01", '2026-12-31'))->toThrow(InvalidArgumentException::class);
});
