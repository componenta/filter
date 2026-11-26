<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('does not let float tolerance round an exact configured divisor to a different value', function (): void {
    expect((new MultipleOfFilter('9007199254740993'))->accept(9007199254740992.0))->toBeFalse()
        ->and((new MultipleOfFilter('1.0000000000000001'))->accept(1.0))->toBeFalse()
        ->and((new MultipleOfFilter('0.10000000000000000000000000000001'))->accept(0.3))->toBeFalse()
        ->and((new MultipleOfFilter('0.10000000000000000000000000000001'))->accept('0.30000000000000000000000000000003'))->toBeTrue()
        ->and((new MultipleOfFilter('0.1'))->accept(0.30000000000000004))->toBeTrue()
        ->and((new MultipleOfFilter(0.1))->accept(0.30000000000000004))->toBeTrue();
});

it('does not round an exact integer divisor beyond float integer resolution', function (): void {
    expect((new MultipleOfFilter(9007199254740993))->accept(9007199254740992.0))->toBeFalse()
        ->and((new MultipleOfFilter(9007199254740992))->accept(9007199254740992.0))->toBeTrue();
})->skip(PHP_INT_SIZE !== 8, 'Requires 64-bit integer divisor');
