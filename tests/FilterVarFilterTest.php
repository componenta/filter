<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('distinguishes a valid false boolean from validation failure', function (): void {
    $filter = new FilterVarFilter(FILTER_VALIDATE_BOOLEAN);

    expect($filter->accept(false))->toBeTrue()
        ->and($filter->accept('false'))->toBeTrue()
        ->and($filter->accept('not-a-boolean'))->toBeFalse();
});

it('rejects an unknown filter id at construction time', function (): void {
    new FilterVarFilter(PHP_INT_MAX);
})->throws(InvalidArgumentException::class);

it('rejects missing and invalid regular expression options at construction time', function (array|int $options): void {
    new FilterVarFilter(FILTER_VALIDATE_REGEXP, $options);
})->with([
    'missing regexp option' => 0,
    'missing regexp key' => [['options' => []]],
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
