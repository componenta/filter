<?php

declare(strict_types=1);

use Componenta\Filter\RegexFilter;

it('rejects an invalid regular expression at construction time', function (): void {
    new RegexFilter('/[');
})->throws(InvalidArgumentException::class);

it('continues to match a valid regular expression', function (): void {
    $filter = new RegexFilter('/^foo\d+$/');

    expect($filter->accept('foo42'))->toBeTrue()
        ->and($filter->accept('bar42'))->toBeFalse();
});
