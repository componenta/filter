<?php

declare(strict_types=1);

use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\StringEqualsAnyFilter;

it('preserves identical string matching on first and subsequent calls and immutable copies', function (): void {
    $configured = ['foo', '01', "A\0b", 'Ä', ''];
    $cases = ['foo', 'FOO', '01', '1', "a\0B", 'Ä', 'ä', '', 'missing'];

    foreach ([true, false] as $caseSensitive) {
        $filter = new StringEqualsAnyFilter($configured, $caseSensitive);

        foreach ($cases as $value) {
            $expected = false;
            foreach ($configured as $candidate) {
                if ($caseSensitive ? $candidate === $value : strcasecmp($candidate, $value) === 0) {
                    $expected = true;
                    break;
                }
            }

            expect((new StringEqualsAnyFilter($configured, $caseSensitive))->accept($value))->toBe($expected)
                ->and($filter->accept($value))->toBe($expected)
                ->and($filter->accept($value))->toBe($expected)
                ->and($filter->withIterable([$value])->toArray())->toBe($expected ? [$value] : []);
        }
    }
});

it('preserves extension matching across repeated calls and configuration changes', function (): void {
    $filter = new FileExtensionFilter(['TXT', '01']);
    foreach (['x.txt' => true, 'x.TXT' => true, 'x.01' => true, 'x.1' => false, 'README' => false] as $value => $expected) {
        expect((new FileExtensionFilter(['TXT', '01']))->accept($value))->toBe($expected)
            ->and($filter->accept($value))->toBe($expected)
            ->and($filter->accept($value))->toBe($expected);
    }

    expect($filter->withCaseSensitive(true)->accept('x.txt'))->toBeFalse()
        ->and($filter->withAllowedExtensions(['php'])->accept('x.txt'))->toBeFalse()
        ->and($filter->accept('x.txt'))->toBeTrue();
});
