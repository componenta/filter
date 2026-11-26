<?php

declare(strict_types=1);

use Componenta\Filter\AnyClassFilter;
use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\InstanceofAnyFilter;
use Componenta\Filter\KeyExcludeFilter;
use Componenta\Filter\KeyInFilter;
use Componenta\Filter\ReflectionConcreteClassFilter;
use Componenta\Filter\StringEqualsAnyFilter;

it('owns validated string list entries instead of retaining external references', function (): void {
    $class = stdClass::class;
    $classes = ['original' => &$class];
    $exact = new AnyClassFilter($classes);
    $instance = new InstanceofAnyFilter($classes);
    $reflection = new ReflectionConcreteClassFilter($classes);
    $class = DateTimeImmutable::class;

    expect($exact->accept(new stdClass()))->toBeTrue()
        ->and($instance->accept(new stdClass()))->toBeTrue()
        ->and($reflection->accept(new ReflectionClass(stdClass::class)))->toBeTrue()
        ->and($exact->accept(new DateTimeImmutable()))->toBeFalse()
        ->and($exact->getAllowedClasses())->toBe(['original' => stdClass::class]);

    $word = 'admin';
    $filter = new StringEqualsAnyFilter([&$word]);
    $word = 'guest';
    $returned = $filter->getAllowed();
    $returned[0] = 'guest';

    expect($filter->accept('admin'))->toBeTrue()
        ->and($filter->accept('guest'))->toBeFalse();

    $extension = 'txt';
    $file = new FileExtensionFilter([&$extension]);
    $extension = [];
    expect($file->accept('file.TXT'))->toBeTrue();
});

it('owns validated key entries independently of caller references', function (): void {
    $key = 'private';
    $allowed = new KeyInFilter([&$key]);
    $excluded = new KeyExcludeFilter([&$key]);
    $key = 'public';

    expect($allowed->accept(null, 'private'))->toBeTrue()
        ->and($allowed->accept(null, 'public'))->toBeFalse()
        ->and($excluded->accept(null, 'private'))->toBeFalse()
        ->and($excluded->accept(null, 'public'))->toBeTrue();
});
