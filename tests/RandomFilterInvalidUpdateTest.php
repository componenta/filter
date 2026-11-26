<?php

declare(strict_types=1);

use Componenta\Filter\RandomFilter;
use Random\Engine;
use Random\Randomizer;

it('rejects invalid replacement probabilities before invoking the engine copy protocol', function (): void {
    $engine = new class implements Engine {
        public function generate(): string
        {
            return str_repeat("\0", 8);
        }

        public function __clone(): void
        {
            throw new LogicException('Engine clone must not be attempted for invalid configuration');
        }
    };
    $filter = new RandomFilter(randomizer: new Randomizer($engine));

    foreach ([-0.1, 1.1, INF, -INF, NAN] as $invalid) {
        expect(fn() => $filter->withProbability($invalid))->toThrow(InvalidArgumentException::class);
    }

    expect($filter->getProbability())->toBe(0.5)
        ->and($filter->accept(null))->toBeTrue();
});
