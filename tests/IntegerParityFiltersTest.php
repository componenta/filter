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

it('continues to accept integral numeric forms and reject fractions', function (): void {
    $even = new EvenNumberFilter();
    $odd = new OddNumberFilter();

    expect($even->accept(2))->toBeTrue()
        ->and($odd->accept(-3))->toBeTrue()
        ->and($even->accept('2e3'))->toBeTrue()
        ->and($even->accept(2.5))->toBeFalse()
        ->and($odd->accept(3.5))->toBeFalse();
});
