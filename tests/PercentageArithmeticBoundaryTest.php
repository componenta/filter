<?php

declare(strict_types=1);

use Componenta\Filter\PercentageFilter;

it('uses exact arithmetic for common fractions and repeating decimal float representations', function (): void {
    $data = range(1, 1000);

    foreach ([
        [29.5, 295], [2.9, 29], [2.8999999999999995, 28],
        [2.9000000000000004, 29], [99.99999999999999, 999],
        [0.09999999999999999, 0], [0.1, 1],
    ] as [$percentage, $expected]) {
        expect((new PercentageFilter($percentage, $data))->toArray())->toBe(array_slice($data, 0, $expected));
    }
});

it('handles an extreme advertised count without overflowing or losing the decimal boundary', function (): void {
    $source = new class implements IteratorAggregate, Countable {
        public int $visited = 0;

        public function count(): int
        {
            return PHP_INT_MAX;
        }

        public function getIterator(): Traversable
        {
            foreach (range(1, 20) as $value) {
                $this->visited++;
                yield $value;
            }
        }
    };

    expect((new PercentageFilter(1e-17, $source))->toArray())->toBe([])
        ->and($source->visited)->toBe(0)
        ->and((new PercentageFilter(2e-17, $source))->toArray())->toBe([1])
        ->and($source->visited)->toBe(1)
        ->and((new PercentageFilter(1e-16, $source))->toArray())->toBe(range(1, 9))
        ->and($source->visited)->toBe(10)
        ->and((new PercentageFilter(PHP_FLOAT_MIN, $source))->toArray())->toBe([])
        ->and($source->visited)->toBe(10);
})->skip(PHP_INT_SIZE !== 8, 'Requires 64-bit integer count');

it('rejects negative Countable sizes instead of yielding an element', function (): void {
    $source = new class implements IteratorAggregate, Countable {
        public function count(): int
        {
            return -1;
        }

        public function getIterator(): Traversable
        {
            throw new LogicException('Invalid source must not be opened');
        }
    };

    expect(fn() => (new PercentageFilter(50, $source))->toArray())->toThrow(UnexpectedValueException::class);
});
