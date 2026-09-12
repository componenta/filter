<?php

declare(strict_types=1);

use Componenta\Filter\EvenNumberFilter;
use Componenta\Filter\OddNumberFilter;

it('determines parity of integer strings beyond the platform integer range', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($even->accept('9223372036854775808'))->toBeTrue()
        ->and($odd->accept('9223372036854775808'))->toBeFalse()
        ->and($even->accept('9223372036854775809'))->toBeFalse()
        ->and($odd->accept('9223372036854775809'))->toBeTrue();
});

it('keeps native integer parity exact at platform boundaries', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($odd->accept(PHP_INT_MAX))->toBeTrue()
        ->and($even->accept(PHP_INT_MAX))->toBeFalse()
        ->and($even->accept(PHP_INT_MAX - 1))->toBeTrue()
        ->and($odd->accept(PHP_INT_MAX - 1))->toBeFalse();
});

it('determines parity of exact decimal and scientific integer strings without float rounding', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($odd->accept('9223372036854775809.0'))->toBeTrue()
        ->and($even->accept('9223372036854775809.0'))->toBeFalse()
        ->and($even->accept('9223372036854775809e1'))->toBeTrue()
        ->and($odd->accept('92233720368547758090e-1'))->toBeTrue()
        ->and($odd->accept('1.5e1'))->toBeTrue()
        ->and($even->accept('1.5e0'))->toBeFalse()
        ->and($odd->accept('1.5e0'))->toBeFalse();
});

it('handles decimal shifts and arbitrarily large exponents without overflow', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($odd->accept('.5e1'))->toBeTrue()
        ->and($odd->accept('30e-1'))->toBeTrue()
        ->and($odd->accept('1000e-3'))->toBeTrue()
        ->and($even->accept('0e-999999999999999999999'))->toBeTrue()
        ->and($even->accept('1e999999999999999999999'))->toBeTrue()
        ->and($even->accept('1e-999999999999999999999'))->toBeFalse()
        ->and($odd->accept('1e-999999999999999999999'))->toBeFalse();
});

it('continues to accept integral numeric forms and reject fractions', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($even->accept(2))->toBeTrue()
        ->and($odd->accept(-3))->toBeTrue()
        ->and($even->accept('2e3'))->toBeTrue()
        ->and($even->accept(2.5))->toBeFalse()
        ->and($odd->accept(3.5))->toBeFalse();
});

it('determines parity of integral floats without coercion loss', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($even->accept(2.0))->toBeTrue()
        ->and($odd->accept(2.0))->toBeFalse()
        ->and($even->accept(-4.0))->toBeTrue()
        ->and($odd->accept(-3.0))->toBeTrue()
        ->and($even->accept(-3.0))->toBeFalse();
});

it('allows surrounding whitespace in numeric string parity inputs', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($even->accept(" \t+2.0\n"))->toBeTrue()
        ->and($odd->accept("\r -3 \t"))->toBeTrue();
});
