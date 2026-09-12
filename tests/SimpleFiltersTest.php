<?php

declare(strict_types=1);

use Componenta\Filter\AlphaNumericFilter;
use Componenta\Filter\AnyClassFilter;
use Componenta\Filter\ArrayFilter;
use Componenta\Filter\BoolFilter;
use Componenta\Filter\CallableFilter;
use Componenta\Filter\ChainableFilter;
use Componenta\Filter\ConcreteClassFilter;
use Componenta\Filter\ContainsFilter;
use Componenta\Filter\DirectoryFilter;
use Componenta\Filter\EmptyFilter;
use Componenta\Filter\EndsWithFilter;
use Componenta\Filter\EqualsAnyFilter;
use Componenta\Filter\EqualsFilter;
use Componenta\Filter\ExcludeFilter;
use Componenta\Filter\FileExistsFilter;
use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\FloatFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\InArrayFilter;
use Componenta\Filter\InstanceofAnyFilter;
use Componenta\Filter\InstanceofFilter;
use Componenta\Filter\IntFilter;
use Componenta\Filter\IsInterfaceFilter;
use Componenta\Filter\KeyExcludeFilter;
use Componenta\Filter\KeyInFilter;
use Componenta\Filter\LengthRangeFilter;
use Componenta\Filter\MaxLengthFilter;
use Componenta\Filter\MinLengthFilter;
use Componenta\Filter\NonNullFilter;
use Componenta\Filter\NotEmptyFilter;
use Componenta\Filter\NotEqualsAnyFilter;
use Componenta\Filter\NotEqualsFilter;
use Componenta\Filter\NotFilter;
use Componenta\Filter\NullFilter;
use Componenta\Filter\NumericFilter;
use Componenta\Filter\ObjectFilter;
use Componenta\Filter\PropertyExistsFilter;
use Componenta\Filter\ReflectionAttributeFilter;
use Componenta\Filter\ReflectionConcreteClassFilter;
use Componenta\Filter\ScalarFilter;
use Componenta\Filter\StartsWithFilter;
use Componenta\Filter\StringEqualsAnyFilter;
use Componenta\Filter\StringEqualsFilter;
use Componenta\Filter\StringFilter;
use Componenta\Filter\SubclassFilter;

interface SimpleFilterContractFixture
{
}

class SimpleFilterParentFixture
{
}

class SimpleFilterChildFixture extends SimpleFilterParentFixture implements SimpleFilterContractFixture
{
}

class SimpleFilterSiblingFixture extends SimpleFilterParentFixture
{
}

#[Attribute(Attribute::TARGET_CLASS)]
final class SimpleFilterMarkerFixture
{
}

#[SimpleFilterMarkerFixture]
final class SimpleFilterAttributedFixture
{
    public string $status = 'active';
}

it('enforces primitive type contracts', function (): void {
    $stringable = new class implements Stringable {
        public function __toString(): string
        {
            return 'value';
        }
    };

    expect((new ArrayFilter())->accept([]))->toBeTrue()
        ->and((new ArrayFilter())->accept(new ArrayObject()))->toBeFalse()
        ->and((new BoolFilter())->accept(false))->toBeTrue()
        ->and((new BoolFilter())->accept(0))->toBeFalse()
        ->and((new CallableFilter())->accept('strlen'))->toBeTrue()
        ->and((new CallableFilter())->accept('definitely_missing_callable'))->toBeFalse()
        ->and((new FloatFilter())->accept(1.0))->toBeTrue()
        ->and((new FloatFilter())->accept(1))->toBeFalse()
        ->and((new IntFilter())->accept(1))->toBeTrue()
        ->and((new IntFilter())->accept('1'))->toBeFalse()
        ->and((new NumericFilter())->accept('1.25'))->toBeTrue()
        ->and((new NumericFilter())->accept('one'))->toBeFalse()
        ->and((new ObjectFilter())->accept(new stdClass()))->toBeTrue()
        ->and((new ObjectFilter())->accept([]))->toBeFalse()
        ->and((new ScalarFilter())->accept('value'))->toBeTrue()
        ->and((new ScalarFilter())->accept(null))->toBeFalse()
        ->and((new StringFilter())->accept($stringable))->toBeTrue()
        ->and((new StringFilter())->accept(1))->toBeFalse()
        ->and((new NullFilter())->accept(null))->toBeTrue()
        ->and((new NonNullFilter())->accept(null))->toBeFalse();
});

it('follows PHP empty semantics explicitly', function (): void {
    expect((new EmptyFilter())->accept('0'))->toBeTrue()
        ->and((new EmptyFilter())->accept('value'))->toBeFalse()
        ->and((new NotEmptyFilter())->accept('0'))->toBeFalse()
        ->and((new NotEmptyFilter())->accept('value'))->toBeTrue();
});

it('distinguishes strict and loose equality', function (): void {
    expect((new EqualsFilter(1))->accept(1))->toBeTrue()
        ->and((new EqualsFilter(1))->accept('1'))->toBeFalse()
        ->and((new EqualsFilter(1, strict: false))->accept('1'))->toBeTrue()
        ->and((new NotEqualsFilter(1))->accept('1'))->toBeTrue()
        ->and((new NotEqualsFilter(1, strict: false))->accept('1'))->toBeFalse()
        ->and((new EqualsAnyFilter([1, 2]))->accept(2))->toBeTrue()
        ->and((new NotEqualsAnyFilter([1, 2]))->accept(3))->toBeTrue()
        ->and((new InArrayFilter([1, 2]))->accept(1))->toBeTrue()
        ->and((new ExcludeFilter([1, 2]))->accept(1))->toBeFalse();
});

it('compares filter keys strictly', function (): void {
    expect((new KeyInFilter(['1']))->accept('value', '1'))->toBeTrue()
        ->and((new KeyInFilter(['1']))->accept('value', 1))->toBeFalse()
        ->and((new KeyExcludeFilter(['blocked']))->accept('value', 'blocked'))->toBeFalse()
        ->and((new KeyExcludeFilter(['blocked']))->accept('value', 'allowed'))->toBeTrue();
});

it('matches class, subclass, and interface relationships', function (): void {
    $child = new SimpleFilterChildFixture();

    expect((new InstanceofFilter(SimpleFilterParentFixture::class))->accept($child))->toBeTrue()
        ->and((new InstanceofAnyFilter([stdClass::class, SimpleFilterParentFixture::class]))->accept($child))->toBeTrue()
        ->and((new ConcreteClassFilter(SimpleFilterChildFixture::class))->accept($child))->toBeTrue()
        ->and((new ConcreteClassFilter(SimpleFilterParentFixture::class))->accept($child))->toBeFalse()
        ->and((new AnyClassFilter([SimpleFilterSiblingFixture::class, SimpleFilterChildFixture::class]))->accept($child))->toBeTrue()
        ->and((new SubclassFilter(SimpleFilterParentFixture::class))->accept(SimpleFilterChildFixture::class))->toBeTrue()
        ->and((new IsInterfaceFilter())->accept(SimpleFilterContractFixture::class))->toBeTrue()
        ->and((new IsInterfaceFilter())->accept(SimpleFilterChildFixture::class))->toBeFalse()
        ->and((new InstanceofFilter(SimpleFilterParentFixture::class))->accept('not-an-object'))->toBeFalse()
        ->and((new InstanceofAnyFilter([SimpleFilterParentFixture::class]))->accept('not-an-object'))->toBeFalse()
        ->and((new ConcreteClassFilter(SimpleFilterChildFixture::class))->accept('not-an-object'))->toBeFalse()
        ->and((new AnyClassFilter([SimpleFilterChildFixture::class]))->accept('not-an-object'))->toBeFalse();
});

it('matches reflection attributes and property existence', function (): void {
    $reflection = new ReflectionClass(SimpleFilterAttributedFixture::class);
    $object = new SimpleFilterAttributedFixture();

    expect((new ReflectionConcreteClassFilter([SimpleFilterAttributedFixture::class]))->accept($reflection))->toBeTrue()
        ->and((new ReflectionAttributeFilter(SimpleFilterMarkerFixture::class))->accept($reflection))->toBeTrue()
        ->and((new PropertyExistsFilter('status'))->accept($object))->toBeTrue()
        ->and((new PropertyExistsFilter('missing'))->accept($object))->toBeFalse();
});

it('applies string predicates with their configured case sensitivity', function (): void {
    expect((new AlphaNumericFilter())->accept('abc123'))->toBeTrue()
        ->and((new AlphaNumericFilter())->accept('abc-123'))->toBeFalse()
        ->and((new ContainsFilter('WORLD', caseSensitive: false))->accept('hello world'))->toBeTrue()
        ->and((new StartsWithFilter('HELLO', caseSensitive: false))->accept('hello world'))->toBeTrue()
        ->and((new EndsWithFilter('WORLD', caseSensitive: false))->accept('hello world'))->toBeTrue()
        ->and((new StringEqualsFilter('HELLO', caseSensitive: false))->accept('hello'))->toBeTrue()
        ->and((new StringEqualsAnyFilter(['HELLO', 'WORLD'], caseSensitive: false))->accept('world'))->toBeTrue()
        ->and((new MinLengthFilter(3))->accept('abc'))->toBeTrue()
        ->and((new MaxLengthFilter(3))->accept('abcd'))->toBeFalse()
        ->and((new LengthRangeFilter(2, 4))->accept('abc'))->toBeTrue();
});

it('matches filesystem filters against real test paths', function (): void {
    expect((new FileExistsFilter())->accept(__FILE__))->toBeTrue()
        ->and((new DirectoryFilter())->accept(__DIR__))->toBeTrue()
        ->and((new FileExtensionFilter(['php']))->accept(__FILE__))->toBeTrue()
        ->and((new FileExtensionFilter(['PHP'], caseSensitive: true))->accept(__FILE__))->toBeFalse();
});

it('composes negation and AND predicates', function (): void {
    $chain = ChainableFilter::from(
        [1, '2', 3],
        new IntFilter(),
        new GreaterThanFilter(1),
    );

    expect((new NotFilter(new IntFilter()))->accept('1'))->toBeTrue()
        ->and((new NotFilter(new IntFilter()))->accept(1))->toBeFalse()
        ->and($chain->toArray())->toBe([3]);
});
