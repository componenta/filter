<?php

declare(strict_types=1);

use Componenta\Filter\ArrayDiffFilter;
use Componenta\Filter\ArrayIntersectFilter;
use Componenta\Filter\EqualsAnyFilter;
use Componenta\Filter\ExcludeFilter;
use Componenta\Filter\InArrayFilter;
use Componenta\Filter\NotEqualsAnyFilter;

it('preserves string comparison with binary strings, scalar types and numeric-looking strings', function (): void {
    $allowed = [1, false, null, '01', "\0", '0'];
    $intersect = new ArrayIntersectFilter($allowed);
    $diff = new ArrayDiffFilter($allowed);

    foreach (['1', 1.0, '', false, null, '01', "\0", 0] as $value) {
        expect($intersect->accept([$value]))->toBeTrue()
            ->and($diff->accept([$value]))->toBeFalse();
    }

    foreach (['001', '1.0', 'not present', [], new stdClass()] as $value) {
        expect($intersect->accept([$value]))->toBeFalse()
            ->and($diff->accept([$value]))->toBeTrue();
    }
});

it('keeps dynamic Stringable comparisons in their original order', function (): void {
    $candidate = new class implements Stringable {
        public int $calls = 0;
        public function __toString(): string
        {
            $this->calls++;
            return $this->calls === 1 ? 'miss' : 'match';
        }
    };
    $filter = new ArrayIntersectFilter([$candidate, 'match']);

    expect($filter->accept(['match']))->toBeTrue()
        ->and($candidate->calls)->toBe(1)
        ->and($filter->accept(['match']))->toBeTrue()
        ->and($candidate->calls)->toBe(2);
});

it('does not cache the runtime-dependent string representation of a float', function (): void {
    $previous = ini_get('precision');
    $filter = new ArrayIntersectFilter([1.23456]);
    try {
        ini_set('precision', '3');
        expect($filter->accept(['1.23']))->toBeTrue();
        ini_set('precision', '6');
        expect($filter->accept(['1.23456']))->toBeTrue()
            ->and($filter->accept(['1.23']))->toBeFalse();
    } finally {
        ini_set('precision', (string) $previous);
    }
});

it('owns arbitrary list slots without copying object identities', function (): void {
    $value = 'admin';
    $object = new stdClass();
    $list = [&$value, $object];
    $intersect = new ArrayIntersectFilter($list);
    $diff = new ArrayDiffFilter($list);
    $equals = new EqualsAnyFilter($list);
    $notEquals = new NotEqualsAnyFilter($list);
    $in = new InArrayFilter($list);
    $exclude = new ExcludeFilter($list);

    $value = 'guest';

    expect($intersect->accept(['admin']))->toBeTrue()
        ->and($diff->accept(['guest']))->toBeTrue()
        ->and($equals->accept('admin'))->toBeTrue()
        ->and($notEquals->accept('guest'))->toBeTrue()
        ->and($in->accept($object))->toBeTrue()
        ->and($exclude->accept($object))->toBeFalse();
});
