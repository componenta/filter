<?php

declare(strict_types=1);

use Componenta\Filter\AnyClassFilter;
use Componenta\Filter\ConcreteClassFilter;
use Componenta\Filter\ReflectionConcreteClassFilter;

final class ClassAliasFilterFixture
{
}

it('treats aliases and case variants as the same concrete class', function (): void {
    $alias = 'ComponentaFilterClassAliasFixture';

    if (!class_exists($alias, false)) {
        class_alias(ClassAliasFilterFixture::class, $alias);
    }

    $object = new ClassAliasFilterFixture();
    $reflection = new ReflectionClass(ClassAliasFilterFixture::class);
    $lowercase = strtolower(ClassAliasFilterFixture::class);

    expect((new ConcreteClassFilter($alias))->accept($object))->toBeTrue()
        ->and((new AnyClassFilter([$alias]))->accept($object))->toBeTrue()
        ->and((new ReflectionConcreteClassFilter([$alias]))->accept($reflection))->toBeTrue()
        ->and((new ConcreteClassFilter($lowercase))->accept($object))->toBeTrue()
        ->and((new AnyClassFilter([$lowercase]))->accept($object))->toBeTrue()
        ->and((new ReflectionConcreteClassFilter([$lowercase]))->accept($reflection))->toBeTrue();
});

it('does not accept an unloaded near-name concrete class candidate', function (): void {
    $missing = substr(ClassAliasFilterFixture::class, 0, -1) . 'd';
    $object = new ClassAliasFilterFixture();
    $reflection = new ReflectionClass(ClassAliasFilterFixture::class);

    expect(class_exists($missing, false))->toBeFalse()
        ->and((new ConcreteClassFilter($missing))->accept($object))->toBeFalse()
        ->and((new AnyClassFilter([$missing]))->accept($object))->toBeFalse()
        ->and((new ReflectionConcreteClassFilter([$missing]))->accept($reflection))->toBeFalse();
});

it('does not autoload unknown exact-class candidates', function (): void {
    $autoloads = 0;
    $missing = 'DefinitelyMissingComponentaConcreteClass';
    $loader = static function (string $class) use (&$autoloads, $missing): void {
        if ($class === $missing) {
            $autoloads++;
        }
    };

    spl_autoload_register($loader);

    try {
        $object = new ClassAliasFilterFixture();
        $reflection = new ReflectionClass(ClassAliasFilterFixture::class);

        expect((new ConcreteClassFilter($missing))->accept($object))->toBeFalse()
            ->and((new AnyClassFilter([$missing]))->accept($object))->toBeFalse()
            ->and((new ReflectionConcreteClassFilter([$missing]))->accept($reflection))->toBeFalse()
            ->and($autoloads)->toBe(0);
    } finally {
        spl_autoload_unregister($loader);
    }
});
