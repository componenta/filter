<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('rejects native integer range options that would emit warnings at runtime', function (): void {
    new FilterVarFilter(
        FILTER_VALIDATE_INT,
        ['options' => ['max_range' => new stdClass()]],
    );
})->throws(InvalidArgumentException::class);

it('rejects native float options that would throw at runtime', function (): void {
    new FilterVarFilter(
        FILTER_VALIDATE_FLOAT,
        ['options' => ['decimal' => '..']],
    );
})->throws(InvalidArgumentException::class);

it('validates unsafe native options inside required-array mode', function (): void {
    new FilterVarFilter(
        FILTER_VALIDATE_INT,
        [
            'flags' => FILTER_REQUIRE_ARRAY,
            'options' => ['max_range' => new stdClass()],
        ],
    );
})->throws(InvalidArgumentException::class);

it('does not leak native option warnings or replace the caller error handler', function (): void {
    $callerWarnings = [];
    set_error_handler(
        static function (int $severity, string $message) use (&$callerWarnings): bool {
            $callerWarnings[] = [$severity, $message];
            return true;
        },
    );

    try {
        error_clear_last();

        expect(fn() => new FilterVarFilter(
            FILTER_VALIDATE_INT,
            ['options' => ['max_range' => new stdClass()]],
        ))->toThrow(InvalidArgumentException::class);

        expect(error_get_last())->toBeNull();

        trigger_error('caller handler restored', E_USER_WARNING);

        expect($callerWarnings)->toHaveCount(1)
            ->and($callerWarnings[0][0])->toBe(E_USER_WARNING)
            ->and($callerWarnings[0][1])->toBe('caller handler restored');
    } finally {
        restore_error_handler();
    }
});
