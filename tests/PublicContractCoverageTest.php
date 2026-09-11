<?php

declare(strict_types=1);

use Componenta\Filter\BetweenFilter;
use Componenta\Filter\ChainableFilter;
use Componenta\Filter\ContainsFilter;
use Componenta\Filter\DateRangeFilter;
use Componenta\Filter\EndsWithFilter;
use Componenta\Filter\IntFilter;
use Componenta\Filter\OneOfFilter;
use Componenta\Filter\PropertyEqualsFilter;
use Componenta\Filter\ReflectionConcreteClassFilter;
use Componenta\Filter\ReflectionImplementingFilter;
use Componenta\Filter\ReflectionSubclassFilter;
use Componenta\Filter\StartsWithFilter;
use Componenta\Filter\StringEqualsAnyFilter;

interface PublicContractCoverageInterface
{
}

class PublicContractCoverageParent
{
}

class PublicContractCoverageChild extends PublicContractCoverageParent implements PublicContractCoverageInterface
{
}

it('rejects finite numeric values outside inclusive Between bounds', function (): void {
    $filter = new BetweenFilter(1, 3);

    expect($filter->accept(0))->toBeFalse()
        ->and($filter->accept(4))->toBeFalse();
});

it('preserves case-sensitive string matching', function (): void {
    expect((new ContainsFilter('WORLD'))->accept('hello world'))->toBeFalse()
        ->and((new StartsWithFilter('HELLO'))->accept('hello world'))->toBeFalse()
        ->and((new EndsWithFilter('WORLD'))->accept('hello world'))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['HELLO']))->accept('hello'))->toBeFalse();
});

it('rejects case-insensitive non-matches', function (): void {
    expect((new ContainsFilter('missing', caseSensitive: false))->accept('hello world'))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['HELLO'], caseSensitive: false))->accept('world'))->toBeFalse();
});

it('rejects invalid date strings and accepts equal range endpoints', function (): void {
    expect(fn() => new DateRangeFilter('not-a-date', '2026-12-31'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn() => new DateRangeFilter('2026-01-01', 'not-a-date'))
        ->toThrow(InvalidArgumentException::class);

    $filter = new DateRangeFilter('2026-01-01', '2026-01-01');

    expect($filter->accept('2026-01-01'))->toBeTrue();
});

it('rejects non-reflection values in reflection class predicates', function (): void {
    $value = new stdClass();

    expect((new ReflectionConcreteClassFilter([PublicContractCoverageChild::class]))->accept($value))->toBeFalse()
        ->and((new ReflectionImplementingFilter(PublicContractCoverageInterface::class))->accept($value))->toBeFalse()
        ->and((new ReflectionSubclassFilter(PublicContractCoverageParent::class))->accept($value))->toBeFalse();
});

it('rejects non-object values in property equality predicates', function (): void {
    expect((new PropertyEqualsFilter('status', 'active'))->accept('not-an-object'))->toBeFalse();
});

it('rejects invalid filters in concrete composite constructors', function (): void {
    expect(fn() => new ChainableFilter(['not-a-filter']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn() => new OneOfFilter(['not-a-filter']))
        ->toThrow(InvalidArgumentException::class);
});

it('honors constructor iterables in filters whose setup is otherwise validated', function (): void {
    $between = new BetweenFilter(1, 2, true, [0, 1, 2, 3]);
    $propertyObject = new class {
        public string $status = 'active';
    };
    $property = new PropertyEqualsFilter('status', 'active', true, [$propertyObject, new stdClass()]);
    $reflection = new ReflectionClass(PublicContractCoverageChild::class);

    expect($between->toArray())->toBe([1, 2])
        ->and($property->toArray())->toBe([$propertyObject])
        ->and((new ReflectionConcreteClassFilter([PublicContractCoverageChild::class], [$reflection]))->toArray())->toBe([$reflection])
        ->and((new ReflectionImplementingFilter(PublicContractCoverageInterface::class, [$reflection]))->toArray())->toBe([$reflection])
        ->and((new ReflectionSubclassFilter(PublicContractCoverageParent::class, [$reflection]))->toArray())->toBe([$reflection]);
});
