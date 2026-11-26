<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\PercentageFilter;
use Componenta\Filter\RecursiveFilter;
use Componenta\Filter\UniqueFilter;

it('normalizes null keys only on array materialization without deprecation', function (): void {
    $source = static function (): Generator {
        yield null => 1;
        yield '' => 2;
        yield '1' => 3;
        yield 1 => 4;
        yield null => 5;
    };
    $factories = [
        static fn() => new IntFilter($source()),
        static fn() => new PercentageFilter(100, $source()),
        static fn() => new UniqueFilter($source()),
        static fn() => new MergingFilter(new IntFilter($source())),
        static fn() => new RecursiveFilter(new IntFilter(), iterable: $source()),
    ];
    set_error_handler(static function (int $severity, string $message): never {
        throw new ErrorException($message, 0, $severity);
    }, E_DEPRECATED | E_USER_DEPRECATED);

    try {
        foreach ($factories as $factory) {
            expect($factory()->toArray(true))->toBe(['' => 5, 1 => 4])
                ->and($factory()->toArray(false))->toBe([1, 2, 3, 4, 5]);
        }

        $cursor = (new IntFilter($source()))->getIterator();
        expect($cursor->key())->toBeNull()
            ->and($cursor->current())->toBe(1);

        $nested = new RecursiveFilter(new IntFilter(), yieldNestedAsArray: true, iterable: [$source()]);
        expect($nested->toArray())->toBe([['' => 5, 1 => 4]]);
    } finally {
        restore_error_handler();
    }
});
