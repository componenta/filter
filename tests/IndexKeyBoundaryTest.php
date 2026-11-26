<?php

declare(strict_types=1);

use Componenta\Filter\KeyExcludeFilter;
use Componenta\Filter\KeyInFilter;
use Componenta\Filter\StringEqualsAnyFilter;
use Componenta\Filter\UniqueFilter;

it('retains strictly distinct values even when their bytes resemble internal index keys', function (): void {
    $values = [
        7, '7', 'i:7', '7i:', null, 'n:', '', 's:', true, false, 'b:1', 'b:0',
        0.0, 7.0, 8.0, 9.0, pack('d', 7.0), 'f:' . pack('d', 7.0),
        's:' . pack('d', 7.0), "\0", "\0\0", '0', 0,
    ];

    $source = array_merge($values, $values, [-0.0]);
    expect((new UniqueFilter($source))->toArray())->toBe($values);
});

it('does not let arbitrary iterator keys collide across PHP key types', function (): void {
    foreach ([
        [7, ['7', 'i:7', '7i:', 's:7', null]],
        ['7', [7, 'i:7', 's:7', '7s:']],
        [null, ['', 'n:', 's:', 0, '0']],
        ['', [null, 'n:', 's:', 0, '0']],
    ] as [$allowed, $different]) {
        $only = new KeyInFilter([$allowed]);
        $except = new KeyExcludeFilter([$allowed]);

        expect($only->accept(null, $allowed))->toBeTrue()
            ->and($except->accept(null, $allowed))->toBeFalse();

        foreach ($different as $key) {
            expect($only->accept(null, $key))->toBeFalse()
                ->and($except->accept(null, $key))->toBeTrue();
        }
    }
});

it('matches the same binary string domain in cold and prepared filters', function (): void {
    $configured = ["\0A", "\xffX", 'i', 'ß', '0', '01', ''];
    $filter = new StringEqualsAnyFilter($configured, false);
    expect($filter->getAllowed())->toBe($configured)
        ->and($filter->withAllowed(['different'])->getAllowed())->toBe(['different']);

    foreach (range(0, 255) as $byte) {
        $value = chr($byte) . 'x';
        $expected = false;

        foreach ($configured as $candidate) {
            if (strcasecmp($candidate, $value) === 0) {
                $expected = true;
                break;
            }
        }

        expect((new StringEqualsAnyFilter($configured, false))->accept($value))->toBe($expected)
            ->and($filter->accept($value))->toBe($expected);
    }

    expect($filter->getAllowed())->toBe($configured);
});
