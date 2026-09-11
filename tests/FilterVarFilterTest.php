<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('distinguishes a valid false boolean from validation failure', function (): void {
    $filter = new FilterVarFilter(FILTER_VALIDATE_BOOLEAN);

    expect($filter->accept(false))->toBeTrue()
        ->and($filter->accept('false'))->toBeTrue()
        ->and($filter->accept('not-a-boolean'))->toBeFalse();
});

it('keeps zero default flags when boolean options are passed as an array', function (): void {
    $filter = new FilterVarFilter(
        FILTER_VALIDATE_BOOLEAN,
        ['options' => []],
    );

    expect($filter->accept('true'))->toBeTrue()
        ->and($filter->accept('false'))->toBeTrue()
        ->and($filter->accept('not-a-boolean'))->toBeFalse();
});

it('validates every member and required shape in filter_var array mode', function (): void {
    $integers = new FilterVarFilter(
        FILTER_VALIDATE_INT,
        ['flags' => FILTER_REQUIRE_ARRAY],
    );
    $booleans = new FilterVarFilter(
        FILTER_VALIDATE_BOOLEAN,
        ['flags' => FILTER_REQUIRE_ARRAY],
    );
    $forcedInteger = new FilterVarFilter(
        FILTER_VALIDATE_INT,
        ['flags' => FILTER_FORCE_ARRAY],
    );

    expect($integers->accept([1, '2', 3]))->toBeTrue()
        ->and($integers->accept([1, 'bad', 3]))->toBeFalse()
        ->and($integers->accept('42'))->toBeFalse()
        ->and($booleans->accept(['true', false, '0']))->toBeTrue()
        ->and($booleans->accept(['true', 'not-a-boolean']))->toBeFalse()
        ->and($booleans->accept('false'))->toBeFalse()
        ->and($forcedInteger->accept('42'))->toBeTrue()
        ->and($forcedInteger->accept('bad'))->toBeFalse();
});

it('honors iterable binding while validating values', function (): void {
    $filter = new FilterVarFilter(
        FILTER_VALIDATE_INT,
        iterable: [
            'first' => '1',
            'invalid' => 'not-an-int',
            'second' => '2',
        ],
    );

    expect($filter->toArray(preserveKeys: true))->toBe([
        'first' => '1',
        'second' => '2',
    ]);
});

it('rejects an unknown filter id at construction time', function (): void {
    new FilterVarFilter(PHP_INT_MAX);
})->throws(InvalidArgumentException::class);

it('rejects non-integer flags at construction time', function (): void {
    new FilterVarFilter(FILTER_VALIDATE_INT, ['flags' => []]);
})->throws(InvalidArgumentException::class);

it('rejects missing and invalid regular expression options at construction time', function (array|int $options): void {
    new FilterVarFilter(FILTER_VALIDATE_REGEXP, $options);
})->with([
    'missing regexp option' => 0,
    'missing regexp key' => [['options' => []]],
    'non-array options container' => [['options' => 'not-an-array']],
    'invalid regexp pattern' => [['options' => ['regexp' => '/[']]],
])->throws(InvalidArgumentException::class);

it('validates values with a configured regular expression', function (): void {
    $filter = new FilterVarFilter(
        FILTER_VALIDATE_REGEXP,
        ['options' => ['regexp' => '/^foo\d+$/']],
    );

    expect($filter->accept('foo42'))->toBeTrue()
        ->and($filter->accept('bar42'))->toBeFalse();
});

it('rejects missing and invalid callback options at construction time', function (array|int $options): void {
    new FilterVarFilter(FILTER_CALLBACK, $options);
})->with([
    'missing callback option' => 0,
    'missing callback key' => [['options' => null]],
    'non-callable callback' => [['options' => 'definitely_missing_callback']],
])->throws(InvalidArgumentException::class);

it('validates values with a configured callback', function (): void {
    $filter = new FilterVarFilter(
        FILTER_CALLBACK,
        ['options' => static fn(mixed $value): mixed => $value === 'accepted' ? $value : false],
    );

    expect($filter->accept('accepted'))->toBeTrue()
        ->and($filter->accept('rejected'))->toBeFalse();
});
