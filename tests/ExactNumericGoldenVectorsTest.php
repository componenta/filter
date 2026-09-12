<?php

declare(strict_types=1);

use Componenta\Filter\EvenNumberFilter;
use Componenta\Filter\GreaterThanEqualsFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\LessThanEqualsFilter;
use Componenta\Filter\LessThanFilter;
use Componenta\Filter\MultipleOfFilter;
use Componenta\Filter\OddNumberFilter;

/**
 * Golden expectations are generated outside PHP by Python's exact
 * fractions.Fraction/decimal.Decimal arithmetic. The production comparator is
 * deliberately not used to derive any expected result in this test.
 *
 * @return array{compare: list<array{string,string,int}>, multiple: list<array{string,string,bool}>, parity: list<array{string,bool,bool}>}
 */
function exactNumericGoldenVectors(): array
{
    static $vectors = null;

    return $vectors ??= json_decode(
        file_get_contents(__DIR__ . '/Fixtures/exact-numeric-vectors.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

it('matches independently generated exact decimal comparison vectors', function (): void {
    foreach (exactNumericGoldenVectors()['compare'] as [$left, $right, $comparison]) {
        $context = sprintf('%s <=> %s', $left, $right);

        expect((new GreaterThanFilter($right))->accept($left))
            ->toBe($comparison > 0, $context)
            ->and((new GreaterThanEqualsFilter($right))->accept($left))
            ->toBe($comparison >= 0, $context)
            ->and((new LessThanFilter($right))->accept($left))
            ->toBe($comparison < 0, $context)
            ->and((new LessThanEqualsFilter($right))->accept($left))
            ->toBe($comparison <= 0, $context);
    }
});

it('matches independently generated exact decimal divisibility vectors', function (): void {
    foreach (exactNumericGoldenVectors()['multiple'] as [$value, $divisor, $expected]) {
        expect((new MultipleOfFilter($divisor))->accept($value))
            ->toBe($expected, sprintf('%s multiple of %s', $value, $divisor));
    }
});

it('matches independently generated exact integer parity vectors', function (): void {
    foreach (exactNumericGoldenVectors()['parity'] as [$value, $even, $odd]) {
        expect((new EvenNumberFilter())->accept($value))
            ->toBe($even, sprintf('%s even', $value))
            ->and((new OddNumberFilter())->accept($value))
            ->toBe($odd, sprintf('%s odd', $value));
    }
});
