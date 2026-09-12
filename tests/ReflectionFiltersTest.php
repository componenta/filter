<?php

declare(strict_types=1);

use Componenta\Filter\ReflectionConcreteClassFilter;
use Componenta\Filter\ReflectionImplementingFilter;
use Componenta\Filter\ReflectionSubclassFilter;

interface ReflectionFilterContractFixture
{
}

class ReflectionFilterParentFixture
{
}

class ReflectionFilterChildFixture extends ReflectionFilterParentFixture implements ReflectionFilterContractFixture
{
}

it('matches valid reflection interface and subclass constraints', function (): void {
    $reflection = new ReflectionClass(ReflectionFilterChildFixture::class);

    expect((new ReflectionImplementingFilter(ReflectionFilterContractFixture::class))->accept($reflection))->toBeTrue()
        ->and((new ReflectionSubclassFilter(ReflectionFilterParentFixture::class))->accept($reflection))->toBeTrue()
        ->and((new ReflectionSubclassFilter(ReflectionFilterContractFixture::class))->accept($reflection))->toBeTrue();
});

it('rejects non-reflection values in reflection class predicates', function (): void {
    $value = new stdClass();

    expect((new ReflectionConcreteClassFilter([ReflectionFilterChildFixture::class]))->accept($value))->toBeFalse()
        ->and((new ReflectionImplementingFilter(ReflectionFilterContractFixture::class))->accept($value))->toBeFalse()
        ->and((new ReflectionSubclassFilter(ReflectionFilterParentFixture::class))->accept($value))->toBeFalse();
});

it('filters constructor iterables through reflection predicates', function (): void {
    $reflection = new ReflectionClass(ReflectionFilterChildFixture::class);
    $other = new ReflectionClass(stdClass::class);

    expect((new ReflectionConcreteClassFilter(
        [ReflectionFilterChildFixture::class],
        [$reflection, $other],
    ))->toArray())->toBe([$reflection])
        ->and((new ReflectionImplementingFilter(
            ReflectionFilterContractFixture::class,
            [$reflection, $other],
        ))->toArray())->toBe([$reflection])
        ->and((new ReflectionSubclassFilter(
            ReflectionFilterParentFixture::class,
            [$reflection, $other],
        ))->toArray())->toBe([$reflection]);
});

it('rejects an unknown interface when configuring an implementing filter', function (): void {
    new ReflectionImplementingFilter('Componenta\\Filter\\Tests\\MissingInterface');
})->throws(InvalidArgumentException::class);

it('rejects an unknown parent class or interface when configuring a subclass filter', function (): void {
    new ReflectionSubclassFilter('Componenta\\Filter\\Tests\\MissingParent');
})->throws(InvalidArgumentException::class);
