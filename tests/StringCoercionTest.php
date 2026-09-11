<?php

declare(strict_types=1);

use Componenta\Filter\AlphaNumericFilter;
use Componenta\Filter\ContainsFilter;
use Componenta\Filter\DateRangeFilter;
use Componenta\Filter\DirectoryFilter;
use Componenta\Filter\EndsWithFilter;
use Componenta\Filter\FileExistsFilter;
use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\LengthRangeFilter;
use Componenta\Filter\MaxLengthFilter;
use Componenta\Filter\MinLengthFilter;
use Componenta\Filter\RegexFilter;
use Componenta\Filter\StartsWithFilter;
use Componenta\Filter\StringEqualsAnyFilter;
use Componenta\Filter\StringEqualsFilter;

dataset('string-value filters', [
    'alphanumeric' => [static fn() => new AlphaNumericFilter()],
    'contains' => [static fn() => new ContainsFilter('value')],
    'starts with' => [static fn() => new StartsWithFilter('value')],
    'ends with' => [static fn() => new EndsWithFilter('value')],
    'string equals' => [static fn() => new StringEqualsFilter('value')],
    'string equals any' => [static fn() => new StringEqualsAnyFilter(['value'])],
    'length range' => [static fn() => new LengthRangeFilter(1, 10)],
    'minimum length' => [static fn() => new MinLengthFilter(1)],
    'maximum length' => [static fn() => new MaxLengthFilter(10)],
    'regular expression' => [static fn() => new RegexFilter('/value/')],
    'directory' => [static fn() => new DirectoryFilter()],
    'file exists' => [static fn() => new FileExistsFilter()],
    'file extension' => [static fn() => new FileExtensionFilter(['txt'])],
    'date range' => [static fn() => new DateRangeFilter('2026-01-01', '2026-12-31')],
]);

it('rejects non-stringable objects without throwing', function (Closure $factory): void {
    expect($factory()->accept(new stdClass()))->toBeFalse();
})->with('string-value filters');

it('rejects scalar values instead of coercing them to strings', function (Closure $factory): void {
    expect($factory()->accept(123))->toBeFalse()
        ->and($factory()->accept(1.25))->toBeFalse()
        ->and($factory()->accept(true))->toBeFalse()
        ->and($factory()->accept(null))->toBeFalse();
})->with('string-value filters');

it('does not treat arrays as the literal word Array', function (): void {
    expect((new AlphaNumericFilter())->accept([]))->toBeFalse();
});

it('continues to support Stringable values where string-like values are accepted', function (): void {
    $value = new class implements Stringable {
        public function __toString(): string
        {
            return 'prefix-value-suffix';
        }
    };

    expect((new ContainsFilter('value'))->accept($value))->toBeTrue();
});
