<?php

declare(strict_types=1);

use Componenta\Filter\ContainsFilter;
use Componenta\Filter\EndsWithFilter;
use Componenta\Filter\StartsWithFilter;
use Componenta\Filter\StringEqualsAnyFilter;

it('preserves case-sensitive matching by default', function (): void {
    expect((new ContainsFilter('WORLD'))->accept('hello world'))->toBeFalse()
        ->and((new StartsWithFilter('HELLO'))->accept('hello world'))->toBeFalse()
        ->and((new EndsWithFilter('WORLD'))->accept('hello world'))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['HELLO']))->accept('hello'))->toBeFalse();
});

it('rejects non-matches when case-insensitive comparison is enabled', function (): void {
    expect((new ContainsFilter('missing', caseSensitive: false))->accept('hello world'))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['HELLO'], caseSensitive: false))->accept('world'))->toBeFalse();
});
