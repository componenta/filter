<?php

declare(strict_types=1);

use Componenta\Filter\PropertyEqualsFilter;

it('compares initialized public properties', function (): void {
    $value = new class {
        public string $status = 'active';
    };

    expect((new PropertyEqualsFilter('status', 'active'))->accept($value))->toBeTrue()
        ->and((new PropertyEqualsFilter('status', 'disabled'))->accept($value))->toBeFalse();
});

it('rejects inaccessible properties instead of throwing', function (): void {
    $private = new class {
        private string $status = 'active';
    };

    $protected = new class {
        protected string $status = 'active';
    };

    $filter = new PropertyEqualsFilter('status', 'active');

    expect($filter->accept($private))->toBeFalse()
        ->and($filter->accept($protected))->toBeFalse();
});

it('rejects uninitialized and static properties instead of throwing', function (): void {
    $uninitialized = new class {
        public string $status;
    };

    $static = new class {
        public static string $status = 'active';
    };

    $filter = new PropertyEqualsFilter('status', 'active');

    expect($filter->accept($uninitialized))->toBeFalse()
        ->and($filter->accept($static))->toBeFalse();
});

it('rejects a virtual write-only property hook instead of throwing', function (): void {
    $writeOnly = new class {
        public string $status {
            set {
            }
        }
    };

    expect((new PropertyEqualsFilter('status', 'active'))->accept($writeOnly))->toBeFalse();
});
