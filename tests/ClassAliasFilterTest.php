<?php

declare(strict_types=1);

use Componenta\Filter\AnyClassFilter;
use Componenta\Filter\ConcreteClassFilter;
use Componenta\Filter\ReflectionConcreteClassFilter;

final class ClassAliasFilterFixture
{
}

it('treats a class alias as the same concrete class', function (): void {
    $alias = 'ComponentaFilterClassAliasFixture';

    if (!class_exists($alias, false)) {
        class_alias(ClassAliasFilterFixture::class, $alias);
    }

    $object = new ClassAliasFilterFixture();
    $reflection = new ReflectionClass(ClassAliasFilterFixture::class);

    expect((new ConcreteClassFilter($alias))->accept($object))->toBeTrue()
        ->and((new AnyClassFilter([$alias]))->accept($object))->toBeTrue()
        ->and((new ReflectionConcreteClassFilter([$alias]))->accept($reflection))->toBeTrue();
});
