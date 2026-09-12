<?php

declare(strict_types=1);

use Componenta\Filter\DirectoryFilter;
use Componenta\Filter\FileExistsFilter;

it('rejects unavailable stream wrappers without leaking warnings or replacing the caller error handler', function (): void {
    $callerWarnings = [];
    set_error_handler(
        static function (int $severity, string $message) use (&$callerWarnings): bool {
            $callerWarnings[] = [$severity, $message];
            return true;
        },
    );

    try {
        $path = 'componenta-filter-missing-wrapper://path';

        expect((new FileExistsFilter())->accept($path))->toBeFalse()
            ->and((new DirectoryFilter())->accept($path))->toBeFalse()
            ->and($callerWarnings)->toBe([]);

        trigger_error('caller handler restored', E_USER_WARNING);

        expect($callerWarnings)->toHaveCount(1)
            ->and($callerWarnings[0][0])->toBe(E_USER_WARNING)
            ->and($callerWarnings[0][1])->toBe('caller handler restored');
    } finally {
        restore_error_handler();
    }
});
