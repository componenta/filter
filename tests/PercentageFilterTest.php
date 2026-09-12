<?php

declare(strict_types=1);

use Componenta\Filter\PercentageFilter;

it('keeps the requested percentage from the beginning of an array', function (): void {
    $filter = new PercentageFilter(50, ['a', 'b', 'c', 'd']);

    expect($filter->toArray())->toBe(['a', 'b']);
});

it('uses floor semantics for fractional allowed counts', function (): void {
    expect((new PercentageFilter(50, ['a', 'b', 'c']))->toArray())->toBe(['a']);

    $generator = (static function (): Generator {
        yield 'a';
        yield 'b';
        yield 'c';
    })();

    expect((new PercentageFilter(50, $generator))->toArray())->toBe(['a']);
});

it('uses the same floor calculation for generic iterables near one hundred percent', function (): void {
    $source = (static function (): Generator {
        yield from range(1, 100);
    })();

    expect((new PercentageFilter(99, $source))->toArray())->toBe(range(1, 99));
});

it('handles exact percentage boundaries without off-by-one behavior', function (): void {
    $hundred = range(1, 100);

    expect((new PercentageFilter(0, $hundred))->toArray())->toBe([])
        ->and((new PercentageFilter(1, $hundred))->toArray())->toBe([1])
        ->and((new PercentageFilter(99, $hundred))->toArray())->toBe(range(1, 99))
        ->and((new PercentageFilter(100, $hundred))->toArray())->toBe($hundred);
});

it('does not consume a generic source at zero percent', function (): void {
    $visited = 0;
    $source = (static function () use (&$visited): Generator {
        foreach ([1, 2, 3] as $value) {
            $visited++;
            yield $value;
        }
    })();

    expect((new PercentageFilter(0, $source))->toArray())->toBe([])
        ->and($visited)->toBe(0);
});

it('does not over-consume countable lazy sources', function (): void {
    $source = new class implements IteratorAggregate, Countable {
        public int $visited = 0;

        public function count(): int
        {
            return 4;
        }

        public function getIterator(): Traversable
        {
            foreach (['a', 'b', 'c', 'd'] as $value) {
                $this->visited++;
                yield $value;
            }
        }
    };

    expect((new PercentageFilter(50, $source))->toArray())->toBe(['a', 'b'])
        ->and($source->visited)->toBe(2);
});

it('does not start a countable lazy source when the rounded allowance is zero', function (): void {
    $source = new class implements IteratorAggregate, Countable {
        public int $visited = 0;

        public function count(): int
        {
            return 4;
        }

        public function getIterator(): Traversable
        {
            foreach (['a', 'b', 'c', 'd'] as $value) {
                $this->visited++;
                yield $value;
            }
        }
    };

    expect((new PercentageFilter(1, $source))->toArray())->toBe([])
        ->and($source->visited)->toBe(0);
});

it('streams a generic source at one hundred percent', function (): void {
    $visited = 0;
    $source = (static function () use (&$visited): Generator {
        foreach ([1, 2, 3] as $value) {
            $visited++;
            yield $value;
        }
    })();

    $iterator = (new PercentageFilter(100, $source))->getIterator();

    expect($visited)->toBe(0);

    $iterator->rewind();

    expect($iterator->current())->toBe(1)
        ->and($visited)->toBe(1);
});

it('does not collapse repeated generator keys before calculating the percentage', function (): void {
    $source = (static function (): Generator {
        yield 'same' => 'a';
        yield 'same' => 'b';
        yield 'third' => 'c';
        yield 'fourth' => 'd';
    })();

    $filter = new PercentageFilter(50, $source);

    expect(iterator_to_array($filter->getIterator(), false))->toBe(['a', 'b']);
});

it('rebinds the iterable immutably as a collection operator', function (): void {
    $filter = new PercentageFilter(50, [1, 2, 3, 4]);
    $changed = $filter->withIterable(['a', 'b', 'c', 'd']);

    expect($changed)->not->toBe($filter)
        ->and($filter->toArray())->toBe([1, 2])
        ->and($changed->toArray())->toBe(['a', 'b']);
});

dataset('invalid percentages', [
    'below zero' => -0.1,
    'above one hundred' => 100.1,
    'NaN' => NAN,
    'positive infinity' => INF,
    'negative infinity' => -INF,
]);

it('rejects invalid percentage configuration', function (float $percentage): void {
    new PercentageFilter($percentage);
})->with('invalid percentages')->throws(InvalidArgumentException::class);
