<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('does not modify caller-owned references when validating arrays or iterable entries', function (): void {
    $value = '42';
    $invalid = 'invalid';
    $nested = ['value' => &$value, 'invalid' => &$invalid];
    $input = ['nested' => &$nested, 'repeat' => &$value];
    $filter = new FilterVarFilter(FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

    expect($filter->accept($input))->toBeFalse()
        ->and($value)->toBe('42')
        ->and($invalid)->toBe('invalid')
        ->and($nested)->toBe(['value' => '42', 'invalid' => 'invalid']);

    $invalid = '13';
    expect($filter->withIterable([$input])->toArray())->toBe([['nested' => ['value' => '42', 'invalid' => '13'], 'repeat' => '42']])
        ->and($value)->toBe('42')
        ->and($invalid)->toBe('13');
});

it('does not expose native sanitizer transformations through shared array slots', function (): void {
    $value = 'a123b';
    $shared = ['value' => &$value];
    $input = ['left' => &$shared, 'right' => &$shared];
    $filter = new FilterVarFilter(FILTER_SANITIZE_NUMBER_INT, FILTER_FORCE_ARRAY);

    expect($filter->accept($input))->toBeTrue()
        ->and($value)->toBe('a123b')
        ->and($shared)->toBe(['value' => 'a123b']);
});

it('evaluates every callback leaf in input order without replacing references with callback results', function (): void {
    $value = '42';
    $invalid = 'bad';
    $shared = ['first' => &$value, 'second' => &$invalid];
    $input = ['left' => &$shared, 'right' => &$shared];
    $seen = [];
    $filter = new FilterVarFilter(FILTER_CALLBACK, [
        'flags' => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE,
        'options' => static function (string $input) use (&$seen): array|false {
            $seen[] = $input;
            return $input === 'bad' ? false : [false, null];
        },
    ]);

    expect($filter->accept($input))->toBeFalse()
        ->and($seen)->toBe(['42', 'bad', '42', 'bad'])
        ->and($value)->toBe('42')
        ->and($invalid)->toBe('bad');
});

it('rejects input cycles without changing referenced leaves and permits deeply nested acyclic input', function (): void {
    $leaf = '42';
    $cycle = ['value' => &$leaf];
    $cycle['self'] = &$cycle;
    $filter = new FilterVarFilter(FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

    expect($filter->accept($cycle))->toBeFalse()
        ->and($leaf)->toBe('42');

    $deep = '42';
    for ($i = 0; $i < 2048; $i++) {
        $deep = [$deep];
    }

    expect($filter->accept($deep))->toBeTrue();
});

it('retains native scalar and array shape flag precedence', function (int $flags): void {
    $filter = new FilterVarFilter(FILTER_VALIDATE_INT, $flags);

    foreach (['42', 'bad', [], [1, ['2']], [1, ['bad']], null] as $value) {
        $native = filter_var($value, FILTER_VALIDATE_INT, $flags);
        $expected = is_array($native)
            ? !in_array(false, array_merge(...array_map(static fn(mixed $item): array => is_array($item) ? $item : [$item], $native)), true)
            : $native !== false;

        expect($filter->accept($value))->toBe($expected, 'flags: ' . $flags);
    }
})->with([
    0,
    FILTER_REQUIRE_SCALAR,
    FILTER_REQUIRE_ARRAY,
    FILTER_FORCE_ARRAY,
    FILTER_REQUIRE_ARRAY | FILTER_REQUIRE_SCALAR,
    FILTER_FORCE_ARRAY | FILTER_REQUIRE_SCALAR,
    FILTER_REQUIRE_ARRAY | FILTER_FORCE_ARRAY,
]);
