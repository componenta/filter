<?php

declare(strict_types=1);

use Componenta\Filter\UniqueFilter;

it('preserves strict scalar equality including binary strings, infinities and signed zero', function (): void {
    $source = [1, '1', 1.0, true, null, false, 0, '', '0', -0.0, 0.0, "\0", "\0", INF, INF, -INF, -INF, 1];
    $result = (new UniqueFilter($source))->toArray();

    expect($result)->toBe([1, '1', 1.0, true, null, false, 0, '', '0', -0.0, "\0", INF, -INF])
        ->and(fdiv(1.0, $result[9]))->toBe(-INF);
});

it('never deduplicates NaN', function (): void {
    $result = (new UniqueFilter([NAN, NAN, NAN]))->toArray();
    expect($result)->toHaveCount(3);
    foreach ($result as $value) {
        expect(is_nan($value))->toBeTrue();
    }
});

it('preserves strict identity of objects and resources and strict array comparison', function (): void {
    $first = new stdClass();
    $second = new stdClass();
    $resource = fopen('php://memory', 'r+');
    $otherResource = fopen('php://memory', 'r+');

    try {
        $source = [$first, $second, $first, [1], ['1'], [1], $resource, $otherResource, $resource];
        expect((new UniqueFilter($source))->toArray())->toBe([$first, $second, [1], ['1'], $resource, $otherResource]);
    } finally {
        fclose($resource);
        fclose($otherResource);
    }
});

it('preserves the first occurrence and its key over a large scalar iterable', function (): void {
    $source = (static function (): Generator {
        for ($i = 0; $i < 20000; $i++) {
            yield 'first-' . $i => $i;
            yield 'duplicate-' . $i => $i;
        }
    })();
    $result = (new UniqueFilter($source))->toArray(true);
    expect($result)->toHaveCount(20000)
        ->and(array_keys($result))->toBe(array_map(static fn(int $i): string => 'first-' . $i, range(0, 19999)))
        ->and(array_values($result))->toBe(range(0, 19999));
});
