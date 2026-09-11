<?php

declare(strict_types=1);

use Componenta\Filter\ReflectionAttributeFilter;

#[Attribute(Attribute::TARGET_ALL)]
final class ReflectionAttributeMarkerFixture
{
}

#[ReflectionAttributeMarkerFixture]
final class ReflectionAttributeTargetFixture
{
    #[ReflectionAttributeMarkerFixture]
    public const FLAG = true;

    #[ReflectionAttributeMarkerFixture]
    public string $property = 'value';

    #[ReflectionAttributeMarkerFixture]
    public function method(#[ReflectionAttributeMarkerFixture] string $parameter): void
    {
    }
}

#[ReflectionAttributeMarkerFixture]
function reflectionAttributeFunctionFixture(): void
{
}

it('accepts every supported reflection target carrying the configured attribute', function (): void {
    $class = new ReflectionClass(ReflectionAttributeTargetFixture::class);
    $method = $class->getMethod('method');
    $property = $class->getProperty('property');
    $parameter = $method->getParameters()[0];
    $constant = $class->getReflectionConstant('FLAG');
    $function = new ReflectionFunction('reflectionAttributeFunctionFixture');

    $filter = new ReflectionAttributeFilter(ReflectionAttributeMarkerFixture::class);

    expect($filter->accept($class))->toBeTrue()
        ->and($filter->accept($method))->toBeTrue()
        ->and($filter->accept($function))->toBeTrue()
        ->and($filter->accept($property))->toBeTrue()
        ->and($filter->accept($parameter))->toBeTrue()
        ->and($filter->accept($constant))->toBeTrue()
        ->and($filter->accept(new stdClass()))->toBeFalse();
});

it('filters supported reflection targets through its iterable contract', function (): void {
    $class = new ReflectionClass(ReflectionAttributeTargetFixture::class);
    $withoutAttribute = new ReflectionClass(stdClass::class);

    $filter = new ReflectionAttributeFilter(
        ReflectionAttributeMarkerFixture::class,
        [$class, $withoutAttribute],
    );

    expect($filter->toArray())->toBe([$class]);
});
