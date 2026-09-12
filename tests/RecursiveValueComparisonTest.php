<?php

declare(strict_types=1);

use Componenta\Filter\EqualsAnyFilter;
use Componenta\Filter\EqualsFilter;
use Componenta\Filter\ExcludeFilter;
use Componenta\Filter\InArrayFilter;
use Componenta\Filter\NotEqualsAnyFilter;
use Componenta\Filter\NotEqualsFilter;
use Componenta\Filter\PropertyEqualsFilter;
use Componenta\Filter\UniqueFilter;

it('preserves equality for the same recursive array value', function (): void {
    $value = [];
    $value['self'] = &$value;

    expect((new EqualsFilter($value))->accept($value))->toBeTrue()
        ->and((new EqualsFilter($value, strict: false))->accept($value))->toBeTrue()
        ->and((new InArrayFilter([$value]))->accept($value))->toBeTrue()
        ->and((new InArrayFilter([$value], strict: false))->accept($value))->toBeTrue()
        ->and(iterator_to_array((new UniqueFilter([$value, $value]))->getIterator(), false))
        ->toHaveCount(1);
});

it('treats distinct recursive arrays as non-equal without throwing', function (): void {
    $left = [];
    $left['self'] = &$left;

    $right = [];
    $right['self'] = &$right;

    expect((new EqualsFilter($left))->accept($right))->toBeFalse()
        ->and((new EqualsFilter($left, strict: false))->accept($right))->toBeFalse()
        ->and((new NotEqualsFilter($left))->accept($right))->toBeTrue()
        ->and((new NotEqualsFilter($left, strict: false))->accept($right))->toBeTrue();
});

it('handles recursive arrays safely in membership filters', function (): void {
    $left = [];
    $left['self'] = &$left;

    $right = [];
    $right['self'] = &$right;

    expect((new EqualsAnyFilter([$left]))->accept($right))->toBeFalse()
        ->and((new NotEqualsAnyFilter([$left]))->accept($right))->toBeTrue()
        ->and((new InArrayFilter([$left]))->accept($right))->toBeFalse()
        ->and((new ExcludeFilter([$left]))->accept($right))->toBeTrue();
});

it('keeps distinct recursive arrays unique without throwing', function (): void {
    $left = [];
    $left['self'] = &$left;

    $right = [];
    $right['self'] = &$right;

    $filter = new UniqueFilter([$left, $right]);

    expect(iterator_to_array($filter->getIterator(), false))->toHaveCount(2);
});

it('handles recursive array properties safely', function (): void {
    $left = [];
    $left['self'] = &$left;

    $right = [];
    $right['self'] = &$right;

    $object = new class {
        public mixed $value;
    };
    $object->value = $left;

    expect((new PropertyEqualsFilter('value', $left))->accept($object))->toBeTrue()
        ->and((new PropertyEqualsFilter('value', $left, strict: false))->accept($object))->toBeTrue()
        ->and((new PropertyEqualsFilter('value', $right))->accept($object))->toBeFalse()
        ->and((new PropertyEqualsFilter('value', $right, strict: false))->accept($object))->toBeFalse();
});
