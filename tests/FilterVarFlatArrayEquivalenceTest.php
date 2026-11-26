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
                $expected = filter_var($value, $id, $scalarFlags) !== $failure;

                expect($required->accept([&$reference, &$reference]))->toBe($expected, $name)
                    ->and($generic->accept([&$reference, &$reference]))->toBe($expected, $name)
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
