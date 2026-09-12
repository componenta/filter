<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\RecursiveFilter;

it('rejects iterable keys outside the predicate key contract', function (mixed $key): void {
    $source = (static function () use ($key): Generator {
        yield $key => 1;
    })();

    (new IntFilter($source))->toArray(false);
})->with([
    'boolean' => true,
    'float' => 1.5,
    'array' => [[1]],
    'object' => new stdClass(),
])->throws(UnexpectedValueException::class, 'Predicate-backed filters require iterable keys');

it('allows null iterable keys from traversables', function (): void {
    $source = (static function (): Generator {
        yield null => 1;
    })();

    expect((new IntFilter($source))->toArray(false))->toBe([1]);
});

it('rejects unsupported keys during recursive predicate traversal', function (): void {
    $source = (static function (): Generator {
        yield new stdClass() => [1, 2];
    })();

    (new RecursiveFilter(new IntFilter(), iterable: $source))->toArray(false);
})->throws(UnexpectedValueException::class, 'Predicate-backed filters require iterable keys');
