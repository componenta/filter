<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('checks multi-limb coprime divisibility across long-division correction paths', function (
    string $value,
    string $divisor,
    bool $expected,
): void {
    expect((new MultipleOfFilter($divisor))->accept($value))->toBe($expected);
})->with([
    'exact with quotient correction and no normalization' => [
        '408076300880684530686913983282',
        '518028848815472931',
        true,
    ],
    'exact with normalization and borrow propagation' => [
        '96422544925529482895913846842',
        '113375522197057349',
        true,
    ],
    'exact without normalization' => [
        '297092065646006636458062879574',
        '638154740405950843',
        true,
    ],
    'non-multiple with quotient correction' => [
        '300692025386184277721009997',
        '5174478535883491',
        false,
    ],
]);
