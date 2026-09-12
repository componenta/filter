<?php

declare(strict_types=1);

use Componenta\Filter\ContainsFilter;
use Componenta\Filter\EndsWithFilter;
use Componenta\Filter\StartsWithFilter;
use Componenta\Filter\StringEqualsAnyFilter;
use Componenta\Filter\StringEqualsFilter;

it('preserves case-sensitive matching by default', function (): void {
    expect((new ContainsFilter('WORLD'))->accept('hello world'))->toBeFalse()
        ->and((new StartsWithFilter('HELLO'))->accept('hello world'))->toBeFalse()
        ->and((new EndsWithFilter('WORLD'))->accept('hello world'))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['HELLO']))->accept('hello'))->toBeFalse();
});

it('treats empty prefixes and suffixes as matching every string value', function (): void {
    expect((new StartsWithFilter(''))->accept(''))->toBeTrue()
        ->and((new StartsWithFilter(''))->accept('value'))->toBeTrue()
        ->and((new StartsWithFilter('', caseSensitive: false))->accept('value'))->toBeTrue()
        ->and((new EndsWithFilter(''))->accept(''))->toBeTrue()
        ->and((new EndsWithFilter(''))->accept('value'))->toBeTrue()
        ->and((new EndsWithFilter('', caseSensitive: false))->accept('value'))->toBeTrue();
});

it('rejects non-matches when case-insensitive comparison is enabled', function (): void {
    expect((new ContainsFilter('missing', caseSensitive: false))->accept('hello world'))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['HELLO'], caseSensitive: false))->accept('world'))->toBeFalse();
});

it('rejects non-stringable values in case-insensitive comparisons', function (): void {
    $value = new stdClass();

    expect((new ContainsFilter('x', caseSensitive: false))->accept($value))->toBeFalse()
        ->and((new StringEqualsFilter('x', caseSensitive: false))->accept($value))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['x'], caseSensitive: false))->accept($value))->toBeFalse();
});
