<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('rejects invalid FILTER_VALIDATE_REGEXP configuration without leaking warnings or replacing the caller error handler', function (): void {
    $warnings = [];
    set_error_handler(
        static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        },
        E_WARNING,
    );

    try {
        error_clear_last();

        expect(fn() => new FilterVarFilter(
            FILTER_VALIDATE_REGEXP,
            ['options' => ['regexp' => '/[']],
        ))
            ->toThrow(InvalidArgumentException::class)
            ->and(error_get_last())->toBeNull()
            ->and($warnings)->toBe([]);

        preg_match('/[', '');

        expect($warnings)->toHaveCount(1)
            ->and($warnings[0][0])->toBe(E_WARNING);
    } finally {
        restore_error_handler();
        error_clear_last();
    }
});
