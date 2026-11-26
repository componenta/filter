<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('rejects native sanitizer failures using the configured null failure sentinel', function (int $id): void {
    $filter = new FilterVarFilter($id, FILTER_NULL_ON_FAILURE);
    $required = new FilterVarFilter($id, FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE);
    $forced = new FilterVarFilter($id, FILTER_FORCE_ARRAY | FILTER_NULL_ON_FAILURE);

    expect($filter->accept(new stdClass()))->toBeFalse()
        ->and($filter->accept([]))->toBeFalse()
        ->and($filter->accept(''))->toBeTrue()
        ->and($required->accept(['value', new stdClass()]))->toBeFalse()
        ->and($required->accept(['value', '']))->toBeTrue()
        ->and($forced->accept(new stdClass()))->toBeFalse()
        ->and($forced->accept(''))->toBeTrue();
})->with([FILTER_DEFAULT, FILTER_SANITIZE_EMAIL, FILTER_SANITIZE_NUMBER_INT]);

it('distinguishes a null callback result from native rejection before invocation', function (): void {
    $calls = 0;
    $callback = static function (mixed $value) use (&$calls): null {
        $calls++;
        return null;
    };
    $filter = new FilterVarFilter(FILTER_CALLBACK, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => $callback]);
    $array = $filter->withOptions(['flags' => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE, 'options' => $callback]);

    expect($filter->accept('value'))->toBeTrue()
        ->and($calls)->toBe(1)
        ->and($filter->accept(new stdClass()))->toBeFalse()
        ->and($filter->accept([]))->toBeFalse()
        ->and($calls)->toBe(1)
        ->and($array->accept(['value', new stdClass()]))->toBeFalse()
        ->and($array->accept(['value', 'other']))->toBeTrue()
        ->and($calls)->toBe(4);
});
