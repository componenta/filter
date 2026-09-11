<?php

declare(strict_types=1);

use Componenta\Filter\ArrayDiffFilter;
use Componenta\Filter\ArrayIntersectFilter;

it('does not collapse distinct nested arrays into the same value', function (): void {
    expect((new ArrayIntersectFilter([[2]]))->accept([[1]]))->toBeFalse()
        ->and((new ArrayDiffFilter([[2]]))->accept([[1]]))->toBeTrue();
});

it('compares non-stringable objects by identity without throwing', function (): void {
    $first = new stdClass();
    $second = new stdClass();

    expect((new ArrayIntersectFilter([$first]))->accept([$first]))->toBeTrue()
        ->and((new ArrayIntersectFilter([$first]))->accept([$second]))->toBeFalse()
        ->and((new ArrayDiffFilter([$first]))->accept([$first]))->toBeFalse()
        ->and((new ArrayDiffFilter([$first]))->accept([$second]))->toBeTrue();
});

it('does not throw while comparing recursive arrays', function (): void {
    $first = [];
    $first['self'] = &$first;

    $second = [];
    $second['self'] = &$second;

    expect((new ArrayIntersectFilter([$first]))->accept([$second]))->toBeFalse()
        ->and((new ArrayDiffFilter([$first]))->accept([$second]))->toBeTrue();
});

it('retains scalar string-comparison compatibility', function (): void {
    expect((new ArrayIntersectFilter([1]))->accept(['1']))->toBeTrue()
        ->and((new ArrayDiffFilter([1]))->accept(['1']))->toBeFalse();
});

it('retains PHP resource string-comparison compatibility', function (): void {
    $resource = fopen('php://memory', 'r');

    try {
        $string = (string) $resource;

        expect((new ArrayIntersectFilter([$resource]))->accept([$string]))->toBeTrue()
            ->and((new ArrayDiffFilter([$resource]))->accept([$string]))->toBeFalse();
    } finally {
        fclose($resource);
    }
});
