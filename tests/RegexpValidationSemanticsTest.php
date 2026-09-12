<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;
use Componenta\Filter\RegexFilter;

it('validates regexp syntax independently from runtime matching errors', function (string $pattern): void {
    expect(fn() => new RegexFilter($pattern))->not->toThrow(Throwable::class)
        ->and(fn() => new FilterVarFilter(
            FILTER_VALIDATE_REGEXP,
            ['options' => ['regexp' => $pattern]],
        ))->not->toThrow(Throwable::class);
})->with([
    'runtime error on a non-empty subject' => '~^(?(?=.) (?R) | )$~x',
    'runtime error even on an empty subject' => '~(*LIMIT_MATCH=0).*~',
]);
