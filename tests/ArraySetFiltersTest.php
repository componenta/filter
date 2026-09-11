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

it('checks later scalar candidates after an earlier string mismatch', function (): void {
    expect((new ArrayIntersectFilter(['miss', 'match']))->accept(['match']))->toBeTrue()
        ->and((new ArrayDiffFilter(['miss', 'match']))->accept(['match']))->toBeFalse();
});

it('compares Stringable values by their string representation', function (): void {
    $stringable = static fn(string $value): Stringable => new class($value) implements Stringable {
        public function __construct(private readonly string $value)
        {
        }

        public function __toString(): string
        {
            return $this->value;
        }
    };

    $needle = $stringable('match');

    expect((new ArrayIntersectFilter([$stringable('miss'), $stringable('match')]))->accept([$needle]))->toBeTrue()
        ->and((new ArrayDiffFilter([$stringable('miss'), $stringable('match')]))->accept([$needle]))->toBeFalse();
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


it('retains PHP closed-resource string-comparison compatibility', function (): void {
    $resource = fopen('php://memory', 'r');
    $string = (string) $resource;
    fclose($resource);

    expect(gettype($resource))->toBe('resource (closed)')
        ->and((new ArrayIntersectFilter([$resource]))->accept([$string]))->toBeTrue()
        ->and((new ArrayDiffFilter([$resource]))->accept([$string]))->toBeFalse();
});
