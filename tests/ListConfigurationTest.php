<?php

declare(strict_types=1);

use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\InstanceofAnyFilter;
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
