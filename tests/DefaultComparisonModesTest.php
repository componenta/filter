<?php

declare(strict_types=1);

use Componenta\Filter\EqualsAnyFilter;
use Componenta\Filter\ExcludeFilter;
use Componenta\Filter\InArrayFilter;
use Componenta\Filter\NotEqualsAnyFilter;
use Componenta\Filter\StringEqualsFilter;

it('uses strict and case-sensitive comparison modes by default', function (): void {
    expect((new EqualsAnyFilter([1]))->accept('1'))->toBeFalse()
        ->and((new EqualsAnyFilter([1], strict: false))->accept('1'))->toBeTrue()
        ->and((new NotEqualsAnyFilter([1]))->accept('1'))->toBeTrue()
        ->and((new NotEqualsAnyFilter([1], strict: false))->accept('1'))->toBeFalse()
        ->and((new InArrayFilter([1]))->accept('1'))->toBeFalse()
        ->and((new InArrayFilter([1], strict: false))->accept('1'))->toBeTrue()
        ->and((new ExcludeFilter([1]))->accept('1'))->toBeTrue()
        ->and((new ExcludeFilter([1], strict: false))->accept('1'))->toBeFalse()
        ->and((new StringEqualsFilter('HELLO'))->accept('hello'))->toBeFalse()
        ->and((new StringEqualsFilter('HELLO', caseSensitive: false))->accept('hello'))->toBeTrue();
});
