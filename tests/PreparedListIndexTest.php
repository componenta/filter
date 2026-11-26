<?php

declare(strict_types=1);

use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\KeyExcludeFilter;
use Componenta\Filter\KeyInFilter;
use Componenta\Filter\StringEqualsAnyFilter;

it('keeps numeric and binary string values distinct under string indexing', function (): void {
    $filter = new StringEqualsAnyFilter(['01', '1', "a\0B", 'Ä', '']);
    expect($filter->accept('01'))->toBeTrue()
        ->and($filter->accept('1'))->toBeTrue()
        ->and($filter->accept('001'))->toBeFalse()
        ->and($filter->accept(1))->toBeFalse()
        ->and($filter->accept("A\0b"))->toBeFalse()
        ->and($filter->withCaseSensitive(false)->accept("A\0b"))->toBeTrue()
        ->and($filter->withCaseSensitive(false)->accept('ä'))->toBeFalse()
        ->and($filter->withAllowed(['other'])->accept('01'))->toBeFalse()
        ->and($filter->accept(''))->toBeTrue();
});

it('indexes extension configuration without changing extension matching rules', function (): void {
    $filter = new FileExtensionFilter(['TXT', '01', '']);
    expect($filter->accept('a.txt'))->toBeTrue()
        ->and($filter->accept('a.01'))->toBeTrue()
        ->and($filter->accept('a.1'))->toBeFalse()
        ->and($filter->accept('README'))->toBeFalse()
        ->and($filter->withCaseSensitive(true)->accept('a.txt'))->toBeFalse()
        ->and($filter->withCaseSensitive(true)->accept('a.TXT'))->toBeTrue()
        ->and($filter->withAllowedExtensions(['php'])->accept('a.PHP'))->toBeTrue();
});

it('indexes keys with strict distinction between integers, strings and null', function (): void {
    $keys = [1, '01', null, '', "\0"];
    $allowed = new KeyInFilter($keys);
    $excluded = new KeyExcludeFilter($keys);

    foreach ([1, '01', null, '', "\0"] as $key) {
        expect($allowed->accept(null, $key))->toBeTrue()
            ->and($excluded->accept(null, $key))->toBeFalse();
    }

    foreach (['1', 0, '0', 'other'] as $key) {
        expect($allowed->accept(null, $key))->toBeFalse()
            ->and($excluded->accept(null, $key))->toBeTrue();
    }

    expect($allowed->withAllowedKeys(['1'])->accept(null, '1'))->toBeTrue()
        ->and($excluded->withExcludedKeys(['1'])->accept(null, '1'))->toBeFalse();
});
