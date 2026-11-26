<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('does not let sanitizer defaults mask native failures', function (int $id): void {
    $options = ['options' => ['default' => 'fallback']];

    foreach ([0, FILTER_NULL_ON_FAILURE] as $failureFlag) {
        $scalar = new FilterVarFilter($id, ['flags' => $failureFlag] + $options);
        $required = new FilterVarFilter($id, ['flags' => FILTER_REQUIRE_ARRAY | $failureFlag] + $options);
        $forced = new FilterVarFilter($id, ['flags' => FILTER_FORCE_ARRAY | $failureFlag] + $options);

        expect($scalar->accept(new stdClass()))->toBeFalse()
            ->and($scalar->accept('fallback'))->toBeTrue()
            ->and($required->accept(['value', [new stdClass()]]))->toBeFalse()
            ->and($required->accept(['fallback', ['value']]))->toBeTrue()
            ->and($forced->accept(new stdClass()))->toBeFalse()
            ->and($forced->accept('fallback'))->toBeTrue();
    }
})->with([FILTER_DEFAULT, FILTER_SANITIZE_EMAIL, FILTER_SANITIZE_NUMBER_INT, FILTER_SANITIZE_SPECIAL_CHARS]);

it('treats callback results as atomic values in all native array modes', function (): void {
    $seen = [];
    $callback = static function (mixed $value) use (&$seen): mixed {
        $seen[] = $value;

        return match ($value) {
            'reject' => false,
            'null' => null,
            'zero' => 0,
            'empty' => [],
            default => ['data' => [false, null]],
        };
    };
    $scalar = new FilterVarFilter(FILTER_CALLBACK, ['options' => $callback]);
    expect($scalar->accept('array'))->toBeTrue();

    foreach ([0, FILTER_NULL_ON_FAILURE] as $failureFlag) {
        $required = $scalar->withOptions(['flags' => FILTER_REQUIRE_ARRAY | $failureFlag, 'options' => $callback]);
        $forced = $scalar->withOptions(['flags' => FILTER_FORCE_ARRAY | $failureFlag, 'options' => $callback]);

        $seen = [];
        expect($required->accept(['array', ['null', 'zero'], 'empty']))->toBeTrue()
            ->and($seen)->toBe(['array', 'null', 'zero', 'empty'])
            ->and($required->accept(['array', ['reject']]))->toBeFalse()
            ->and($required->accept(['array', new stdClass()]))->toBeFalse()
            ->and($required->accept('array'))->toBeFalse()
            ->and($forced->accept('array'))->toBeTrue()
            ->and($forced->accept('reject'))->toBeFalse()
            ->and($forced->accept(new stdClass()))->toBeFalse();
    }
});

it('does not hide exceptions raised by a configured callback', function (): void {
    $filter = new FilterVarFilter(FILTER_CALLBACK, [
        'flags' => FILTER_REQUIRE_ARRAY,
        'options' => static fn(): never => throw new DomainException('callback failure'),
    ]);

    expect(fn() => $filter->accept(['value']))->toThrow(DomainException::class, 'callback failure');
});
