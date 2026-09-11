<?php

declare(strict_types=1);

use Componenta\Filter\AnyClassFilter;
use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\InstanceofAnyFilter;
use Componenta\Filter\KeyExcludeFilter;
use Componenta\Filter\KeyInFilter;
use Componenta\Filter\ReflectionConcreteClassFilter;
use Componenta\Filter\StringEqualsAnyFilter;

it('rejects non-string values in string equality configuration', function (): void {
    new StringEqualsAnyFilter(['valid', new stdClass()]);
})->throws(InvalidArgumentException::class);

it('rejects non-string file extensions', function (): void {
    new FileExtensionFilter(['txt', []]);
})->throws(InvalidArgumentException::class);

it('rejects non-string class names in instanceof configuration', function (): void {
    new InstanceofAnyFilter([stdClass::class, []]);
})->throws(InvalidArgumentException::class);

it('rejects non-string concrete class allow-list values', function (): void {
    new AnyClassFilter([stdClass::class, new stdClass()]);
})->throws(InvalidArgumentException::class);

it('rejects non-string reflection class allow-list values', function (): void {
    new ReflectionConcreteClassFilter([stdClass::class, []]);
})->throws(InvalidArgumentException::class);

it('rejects values that cannot be iterable keys in key allow-lists', function (): void {
    new KeyInFilter(['valid', 1, null, []]);
})->throws(InvalidArgumentException::class);

it('rejects values that cannot be iterable keys in key deny-lists', function (): void {
    new KeyExcludeFilter(['valid', new stdClass()]);
})->throws(InvalidArgumentException::class);
