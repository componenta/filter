<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('agrees with native scalar evaluation for detached flat arrays across flags-only filters', function (): void {
    $values = [null, false, true, 0, 1, -7, 1.25, NAN, INF, '', 'false', 'true', '42',
        '0755', '0x2a', 'invalid', "\0", 'a@example.com', 'https://example.com',
        '127.0.0.1', '00:00:00:00:00'];

    foreach (filter_list() as $name) {
        $id = filter_id($name);

        if ($id === FILTER_CALLBACK || $id === FILTER_VALIDATE_REGEXP) {
            continue;
        }

        foreach ([0, FILTER_NULL_ON_FAILURE] as $flags) {
            $scalarFlags = $id === FILTER_VALIDATE_BOOLEAN ? $flags | FILTER_NULL_ON_FAILURE : $flags;
            $failure = ($scalarFlags & FILTER_NULL_ON_FAILURE) !== 0 ? null : false;
            $required = new FilterVarFilter($id, $flags | FILTER_REQUIRE_ARRAY);
            $generic = new FilterVarFilter($id, ['flags' => $flags | FILTER_REQUIRE_ARRAY]);

            expect($required->accept([]))->toBeTrue()
                ->and($generic->accept([]))->toBeTrue();

            foreach ($values as $value) {
                $reference = $value;
                $diagnostics = [];
                set_error_handler(static function (int $severity, string $message) use (&$diagnostics): bool {
                    $diagnostics[] = [$severity, $message];

                    return true;
                });

                try {
                    $expected = filter_var($value, $id, $scalarFlags) !== $failure;
                    $nativeDiagnostics = $diagnostics;
                    $diagnostics = [];
                    filter_var($value, $id, $scalarFlags);
                    $nativeDiagnostics = [...$nativeDiagnostics, ...$diagnostics];

                    $diagnostics = [];
                    $fastResult = $required->accept([&$reference, &$reference]);
                    $fastDiagnostics = $diagnostics;

                    $diagnostics = [];
                    $genericResult = $generic->accept([&$reference, &$reference]);
                    $genericDiagnostics = $diagnostics;
                } finally {
                    restore_error_handler();
                }

                expect($fastResult)->toBe($expected, $name)
                    ->and($genericResult)->toBe($expected, $name)
                    ->and($fastDiagnostics)->toBe($nativeDiagnostics, $name)
                    ->and($genericDiagnostics)->toBe($nativeDiagnostics, $name)
                    ->and(get_debug_type($reference))->toBe(get_debug_type($value));

                if (is_float($value) && is_nan($value)) {
                    expect(is_nan($reference))->toBeTrue();
                } else {
                    expect($reference)->toBe($value);
                }
            }
        }
    }
});

it('preserves observations of later referenced values when a Stringable runs user code', function (): void {
    $later = 'bad';
    $value = new class($later) implements Stringable {
        private mixed $later;

        public function __construct(mixed &$later)
        {
            $this->later = &$later;
        }

        public function __toString(): string
        {
            $this->later = '42';

            return '1';
        }
    };

    $filter = new FilterVarFilter(FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

    expect($filter->accept([$value, &$later]))->toBeTrue()
        ->and($later)->toBe('42');
});

it('preserves nested depth-first evaluation and rejects cycles without changing shared leaves', function (): void {
    $events = [];
    $leaf = '2';
    $shared = ['1', &$leaf];
    $filter = new FilterVarFilter(FILTER_CALLBACK, [
        'flags' => FILTER_REQUIRE_ARRAY,
        'options' => static function (string $value) use (&$events): bool {
            $events[] = $value;

            return $value !== 'bad';
        },
    ]);

    expect($filter->accept(['a', &$shared, [], 'bad', [&$shared], 'z']))->toBeFalse()
        ->and($events)->toBe(['a', '1', '2', 'bad', '1', '2', 'z'])
        ->and($leaf)->toBe('2');

    $cycle = [];
    $cycle['self'] = &$cycle;
    expect($filter->accept([$cycle]))->toBeFalse();
});

it('preserves FILTER_THROW_ON_FAILURE for flat arrays on PHP 8.5', function (): void {
    if (!defined('FILTER_THROW_ON_FAILURE')) {
        $this->markTestSkipped('Requires PHP 8.5');
    }

    $flags = FILTER_REQUIRE_ARRAY | constant('FILTER_THROW_ON_FAILURE');
    $integer = new FilterVarFilter(FILTER_VALIDATE_INT, $flags);
    $boolean = new FilterVarFilter(FILTER_VALIDATE_BOOLEAN, $flags);
    $value = '42';
    $invalid = 'bad';

    expect($integer->accept([&$value, &$value]))->toBeTrue()
        ->and($integer->accept([&$value, &$invalid]))->toBeFalse()
        ->and($value)->toBe('42')
        ->and($invalid)->toBe('bad')
        ->and($boolean->accept(['false', 'true', false]))->toBeTrue()
        ->and($boolean->accept(['false', 'invalid']))->toBeFalse();
});

it('preserves warning handler effects on later referenced input slots', function (): void {
    $calls = 0;
    $later = 'bad';
    $replacement = new class($calls) implements Stringable {
        private int $calls;

        public function __construct(int &$calls)
        {
            $this->calls = &$calls;
        }

        public function __toString(): string
        {
            $this->calls++;

            return '42';
        }
    };
    $warnings = 0;
    set_error_handler(static function () use (&$warnings, &$later, $replacement): bool {
        $warnings++;
        $later = $replacement;

        return true;
    });

    try {
        filter_var(NAN, FILTER_VALIDATE_INT);
        $nativeWarnings = $warnings;

        $later = 'bad';
        $warnings = 0;
        $filter = new FilterVarFilter(FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        expect($filter->accept([NAN, &$later]))->toBeFalse()
            ->and($warnings)->toBe($nativeWarnings)
            ->and($calls)->toBe($nativeWarnings > 0 ? 1 : 0);

        if ($nativeWarnings > 0) {
            expect($later)->toBe($replacement);
        } else {
            expect($later)->toBe('bad');
        }
    } finally {
        restore_error_handler();
    }
});
