<?php

declare(strict_types=1);

use Componenta\Filter\AnyClassFilter;
use Componenta\Filter\ArrayDiffFilter;
use Componenta\Filter\ArrayIntersectFilter;
use Componenta\Filter\CallbackFilter;
use Componenta\Filter\ConcreteClassFilter;
use Componenta\Filter\ContainsFilter;
use Componenta\Filter\EndsWithFilter;
use Componenta\Filter\EqualsAnyFilter;
use Componenta\Filter\EqualsFilter;
use Componenta\Filter\ExcludeFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\InArrayFilter;
use Componenta\Filter\InstanceofAnyFilter;
use Componenta\Filter\InstanceofFilter;
use Componenta\Filter\KeyExcludeFilter;
use Componenta\Filter\KeyInFilter;
use Componenta\Filter\LengthRangeFilter;
use Componenta\Filter\MaxLengthFilter;
use Componenta\Filter\MinLengthFilter;
use Componenta\Filter\MultipleOfFilter;
use Componenta\Filter\NotEqualsAnyFilter;
use Componenta\Filter\NotEqualsFilter;
use Componenta\Filter\NotFilter;
use Componenta\Filter\PropertyExistsFilter;
use Componenta\Filter\RangeFilter;
use Componenta\Filter\RegexFilter;
use Componenta\Filter\StartsWithFilter;
use Componenta\Filter\StringEqualsAnyFilter;
use Componenta\Filter\StringEqualsFilter;
use Componenta\Filter\StringFilter;

final class ConstructorIterableFixture
{
    public string $status = 'active';
}

dataset('constructor iterable filters', [
    'callback' => [static fn(): array => [new CallbackFilter(static fn(mixed $value): bool => $value === 2, [1, 2]), [2]]],
    'starts with' => [static fn(): array => [new StartsWithFilter('a', true, ['apple', 'pear']), ['apple']]],
    'ends with' => [static fn(): array => [new EndsWithFilter('e', true, ['apple', 'pear']), ['apple']]],
    'contains' => [static fn(): array => [new ContainsFilter('pp', true, ['apple', 'pear']), ['apple']]],
    'string equals' => [static fn(): array => [new StringEqualsFilter('yes', true, ['yes', 'no']), ['yes']]],
    'string equals any' => [static fn(): array => [new StringEqualsAnyFilter(['yes'], true, ['yes', 'no']), ['yes']]],
    'regex' => [static fn(): array => [new RegexFilter('/^a/', ['apple', 'pear']), ['apple']]],
    'minimum length' => [static fn(): array => [new MinLengthFilter(4, ['one', 'three']), ['three']]],
    'maximum length' => [static fn(): array => [new MaxLengthFilter(3, ['one', 'three']), ['one']]],
    'length range' => [static fn(): array => [new LengthRangeFilter(3, 3, ['one', 'three']), ['one']]],
    'equals' => [static fn(): array => [new EqualsFilter(2, true, [1, 2]), [2]]],
    'not equals' => [static fn(): array => [new NotEqualsFilter(2, true, [1, 2]), [1]]],
    'equals any' => [static fn(): array => [new EqualsAnyFilter([2], true, [1, 2]), [2]]],
    'not equals any' => [static fn(): array => [new NotEqualsAnyFilter([2], true, [1, 2]), [1]]],
    'in array' => [static fn(): array => [new InArrayFilter([2], true, [1, 2]), [2]]],
    'exclude' => [static fn(): array => [new ExcludeFilter([2], true, [1, 2]), [1]]],
    'greater than' => [static fn(): array => [new GreaterThanFilter(1, [1, 2]), [2]]],
    'range' => [static fn(): array => [new RangeFilter(2, 2, [1, 2]), [2]]],
    'multiple of' => [static fn(): array => [new MultipleOfFilter(2, [2, 3]), [2]]],
    'array intersect' => [static fn(): array => [new ArrayIntersectFilter([2], [[1], [2]]), [[2]]]],
    'array diff' => [static fn(): array => [new ArrayDiffFilter([2], [[1], [2]]), [[1]]]],
    'key in' => [static fn(): array => [new KeyInFilter(['keep'], ['keep' => 1, 'drop' => 2]), [1]]],
    'key exclude' => [static fn(): array => [new KeyExcludeFilter(['drop'], ['keep' => 1, 'drop' => 2]), [1]]],
    'instanceof' => [static function (): array {
        $value = new ConstructorIterableFixture();
        return [new InstanceofFilter(ConstructorIterableFixture::class, [$value, new stdClass()]), [$value]];
    }],
    'instanceof any' => [static function (): array {
        $value = new ConstructorIterableFixture();
        return [new InstanceofAnyFilter([ConstructorIterableFixture::class], [$value, new stdClass()]), [$value]];
    }],
    'concrete class' => [static function (): array {
        $value = new ConstructorIterableFixture();
        return [new ConcreteClassFilter(ConstructorIterableFixture::class, [$value, new stdClass()]), [$value]];
    }],
    'any class' => [static function (): array {
        $value = new ConstructorIterableFixture();
        return [new AnyClassFilter([ConstructorIterableFixture::class], [$value, new stdClass()]), [$value]];
    }],
    'property exists' => [static function (): array {
        $value = new ConstructorIterableFixture();
        return [new PropertyExistsFilter('status', [$value, new stdClass()]), [$value]];
    }],
    'not filter' => [static fn(): array => [new NotFilter(new StringFilter(), [1, 'one']), [1]]],
]);

it('honors iterable values passed directly to concrete filter constructors', function (Closure $factory): void {
    [$filter, $expected] = $factory();

    expect($filter->toArray())->toBe($expected);
})->with('constructor iterable filters');
