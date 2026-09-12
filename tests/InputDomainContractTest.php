<?php

declare(strict_types=1);

use Componenta\Filter\ArrayDiffFilter;
use Componenta\Filter\ArrayIntersectFilter;
use Componenta\Filter\PropertyExistsFilter;
use Componenta\Filter\RangeFilter;

it('rejects values outside predicate input domains', function (): void {
    expect((new ArrayIntersectFilter([1]))->accept('1'))->toBeFalse()
        ->and((new ArrayDiffFilter([1]))->accept('1'))->toBeFalse()
        ->and((new RangeFilter(1, 3))->accept('not numeric'))->toBeFalse()
        ->and((new PropertyExistsFilter('status'))->accept('not an object'))->toBeFalse();
});

it('does not reinterpret class strings as object property values', function (): void {
    $filter = new PropertyExistsFilter('status');

    expect(property_exists(InputDomainPropertyFixture::class, 'status'))->toBeTrue()
        ->and($filter->accept(InputDomainPropertyFixture::class))->toBeFalse();
});

final class InputDomainPropertyFixture
{
    public string $status = 'active';
}
