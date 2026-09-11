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
use Componenta\Filter\FileExtensionFilter;
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

final class BoundaryBehaviorFixture
{
    public string $status = 'active';
}

it('treats empty prefixes and suffixes as matching every string value', function (): void {
    expect((new StartsWithFilter(''))->accept(''))->toBeTrue()
        ->and((new StartsWithFilter(''))->accept('value'))->toBeTrue()
        ->and((new EndsWithFilter(''))->accept(''))->toBeTrue()
        ->and((new EndsWithFilter(''))->accept('value'))->toBeTrue();
});

it('normalizes file extensions case-insensitively on both sides', function (): void {
    expect((new FileExtensionFilter(['php']))->accept('example.PHP'))->toBeTrue()
        ->and((new FileExtensionFilter(['PHP']))->accept('example.php'))->toBeTrue()
        ->and((new FileExtensionFilter(['php']))->accept('README'))->toBeFalse();
});

it('rejects non-stringable values in case-insensitive string predicates', function (): void {
    $value = new stdClass();

    expect((new ContainsFilter('x', caseSensitive: false))->accept($value))->toBeFalse()
        ->and((new StringEqualsFilter('x', caseSensitive: false))->accept($value))->toBeFalse()
        ->and((new StringEqualsAnyFilter(['x'], caseSensitive: false))->accept($value))->toBeFalse();
});

it('rejects values of the wrong runtime type in class-oriented predicates', function (): void {
    expect((new InstanceofFilter(BoundaryBehaviorFixture::class))->accept('not-an-object'))->toBeFalse()
        ->and((new InstanceofAnyFilter([BoundaryBehaviorFixture::class]))->accept('not-an-object'))->toBeFalse()
        ->and((new ConcreteClassFilter(BoundaryBehaviorFixture::class))->accept('not-an-object'))->toBeFalse()
        ->and((new AnyClassFilter([BoundaryBehaviorFixture::class]))->accept('not-an-object'))->toBeFalse();
});

dataset('constructor iterable filters', [
    'callback' => [static fn() => new CallbackFilter(static fn(mixed $value): bool => $value === 2, [1, 2]), [2]],
    'starts with' => [static fn() => new StartsWithFilter('a', true, ['apple', 'pear']), ['apple']],
    'ends with' => [static fn() => new EndsWithFilter('e', true, ['apple', 'pear']), ['apple']],
    'contains' => [static fn() => new ContainsFilter('pp', true, ['apple', 'pear']), ['apple']],
    'string equals' => [static fn() => new StringEqualsFilter('yes', true, ['yes', 'no']), ['yes']],
    'string equals any' => [static fn() => new StringEqualsAnyFilter(['yes'], true, ['yes', 'no']), ['yes']],
    'regex' => [static fn() => new RegexFilter('/^a/', ['apple', 'pear']), ['apple']],
    'minimum length' => [static fn() => new MinLengthFilter(4, ['one', 'three']), ['three']],
    'maximum length' => [static fn() => new MaxLengthFilter(3, ['one', 'three']), ['one']],
    'length range' => [static fn() => new LengthRangeFilter(3, 3, ['one', 'three']), ['one']],
    'equals' => [static fn() => new EqualsFilter(2, true, [1, 2]), [2]],
    'not equals' => [static fn() => new NotEqualsFilter(2, true, [1, 2]), [1]],
    'equals any' => [static fn() => new EqualsAnyFilter([2], true, [1, 2]), [2]],
    'not equals any' => [static fn() => new NotEqualsAnyFilter([2], true, [1, 2]), [1]],
    'in array' => [static fn() => new InArrayFilter([2], true, [1, 2]), [2]],
    'exclude' => [static fn() => new ExcludeFilter([2], true, [1, 2]), [1]],
    'greater than' => [static fn() => new GreaterThanFilter(1, [1, 2]), [2]],
    'range' => [static fn() => new RangeFilter(2, 2, [1, 2]), [2]],
    'multiple of' => [static fn() => new MultipleOfFilter(2, [2, 3]), [2]],
    'array intersect' => [static fn() => new ArrayIntersectFilter([2], [[1], [2]]), [[2]]],
    'array diff' => [static fn() => new ArrayDiffFilter([2], [[1], [2]]), [[1]]],
    'key in' => [static fn() => new KeyInFilter(['keep'], ['keep' => 1, 'drop' => 2]), [1]],
    'key exclude' => [static fn() => new KeyExcludeFilter(['drop'], ['keep' => 1, 'drop' => 2]), [1]],
    'instanceof' => [static fn() => new InstanceofFilter(BoundaryBehaviorFixture::class, [new BoundaryBehaviorFixture(), new stdClass()]), [new BoundaryBehaviorFixture()]],
    'instanceof any' => [static fn() => new InstanceofAnyFilter([BoundaryBehaviorFixture::class], [new BoundaryBehaviorFixture(), new stdClass()]), [new BoundaryBehaviorFixture()]],
    'concrete class' => [static fn() => new ConcreteClassFilter(BoundaryBehaviorFixture::class, [new BoundaryBehaviorFixture(), new stdClass()]), [new BoundaryBehaviorFixture()]],
    'any class' => [static fn() => new AnyClassFilter([BoundaryBehaviorFixture::class], [new BoundaryBehaviorFixture(), new stdClass()]), [new BoundaryBehaviorFixture()]],
    'property exists' => [static fn() => new PropertyExistsFilter('status', [new BoundaryBehaviorFixture(), new stdClass()]), [new BoundaryBehaviorFixture()]],
    'not filter' => [static fn() => new NotFilter(new StringFilter(), [1, 'one']), [1]],
]);

it('honors iterable values passed directly to concrete filter constructors', function (Closure $factory, array $expected): void {
    expect($factory()->toArray())->toEqual($expected);
})->with('constructor iterable filters');
