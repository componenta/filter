<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\RecursiveFilter;

it('accepts exactly the documented number of aggregate wrappers and rejects the next one', function (): void {
    $factory = static function (int $depth, object $state): Traversable {
        $source = new ArrayIterator([1, 'two']);

        for ($i = 0; $i < $depth; $i++) {
            $source = new class($source, $state) implements IteratorAggregate {
                public function __construct(private readonly Traversable $source, private readonly object $state)
                {
                }

                public function getIterator(): Traversable
                {
                    $this->state->opened++;

                    return $this->source;
                }
            };
        }

        return $source;
    };

    foreach ([63, 64] as $depth) {
        $state = (object) ['opened' => 0];
        $merge = (new MergingFilter(new IntFilter()))->withIterable($factory($depth, $state));
        expect($state->opened)->toBe(0)
            ->and($merge->toArray())->toBe([1])
            ->and($merge->toArray())->toBe([1])
            ->and($state->opened)->toBe($depth);

        $state = (object) ['opened' => 0];
        $recursive = new RecursiveFilter(new IntFilter(), iterable: $factory($depth, $state), maxDepth: 0);
        expect($recursive->toArray())->toBe([1])
            ->and($state->opened)->toBe($depth);
    }

    $state = (object) ['opened' => 0];
    $merge = (new MergingFilter(new IntFilter()))->withIterable($factory(65, $state));
    expect(fn() => $merge->toArray())->toThrow(OverflowException::class)
        ->and(fn() => $merge->toArray())->toThrow(OverflowException::class)
        ->and($state->opened)->toBe(64);

    $state = (object) ['opened' => 0];
    $recursive = new RecursiveFilter(new IntFilter(), iterable: $factory(65, $state), maxDepth: 0);
    expect(fn() => $recursive->toArray())->toThrow(OverflowException::class)
        ->and($state->opened)->toBe(64);
});
