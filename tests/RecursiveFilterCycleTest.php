<?php

declare(strict_types=1);

use Componenta\Filter\RecursiveFilter;
use Componenta\Filter\StringFilter;

it('rejects recursive iterable object cycles deterministically', function (): void {
    $cycle = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            yield 'value' => 'accepted';
            yield 'self' => $this;
        }
    };

    $filter = new RecursiveFilter(new StringFilter(), iterable: $cycle);

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(RuntimeException::class, 'Recursive iterable cycle detected');
});

it('rejects direct Iterator self-cycles by object identity', function (): void {
    $cycle = new ArrayIterator();
    $cycle['self'] = $cycle;

    $filter = new RecursiveFilter(new StringFilter(), iterable: $cycle);

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(RuntimeException::class, 'Recursive iterable cycle detected');
});

it('rejects cycles inside IteratorAggregate chains before foreach recursion', function (): void {
    $factory = static fn() => new class implements IteratorAggregate {
        public ?IteratorAggregate $next = null;

        public function getIterator(): Traversable
        {
            return $this->next ?? new EmptyIterator();
        }
    };

    $first = $factory();
    $second = $factory();
    $first->next = $second;
    $second->next = $first;

    $filter = new RecursiveFilter(new StringFilter(), iterable: $first);

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(RuntimeException::class, 'Recursive iterable cycle detected');
});

it('allows non-cyclic nested iterable objects', function (): void {
    $leaf = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            yield 'leaf' => 'accepted';
        }
    };

    $root = new class($leaf) implements IteratorAggregate {
        public function __construct(private readonly IteratorAggregate $leaf) {}

        public function getIterator(): Traversable
        {
            yield 'nested' => $this->leaf;
        }
    };

    $filter = new RecursiveFilter(new StringFilter(), iterable: $root);

    expect(iterator_to_array($filter->getIterator(), false))->toBe(['accepted']);
});

it('may revisit the same iterable object after leaving its active branch', function (): void {
    $shared = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            yield 'value' => 'accepted';
        }
    };

    $filter = new RecursiveFilter(
        new StringFilter(),
        iterable: [$shared, $shared],
    );

    expect(iterator_to_array($filter->getIterator(), false))->toBe(['accepted', 'accepted']);
});

it('bounds self-referential arrays by maximum depth', function (): void {
    $cycle = [];
    $cycle['value'] = 'accepted';
    $cycle['self'] = &$cycle;

    $filter = new RecursiveFilter(
        new StringFilter(),
        iterable: $cycle,
        maxDepth: 3,
    );

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(OverflowException::class, 'Maximum recursive filter depth of 3 exceeded');
});

it('limits recursive array depth instead of recursing indefinitely', function (): void {
    $value = 'accepted';

    for ($i = 0; $i < 4; $i++) {
        $value = [$value];
    }

    $filter = new RecursiveFilter(
        new StringFilter(),
        iterable: [$value],
        maxDepth: 2,
    );

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(OverflowException::class, 'Maximum recursive filter depth of 2 exceeded');
});

it('enforces maximum depth exactly at the zero boundary', function (): void {
    $filter = new RecursiveFilter(
        new StringFilter(),
        iterable: [['one']],
        maxDepth: 0,
    );

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(OverflowException::class, 'Maximum recursive filter depth of 0 exceeded');
});

it('supports explicitly configured finite recursive depth', function (): void {
    $filter = new RecursiveFilter(
        new StringFilter(),
        iterable: [[['one', 2, 'three']]],
        maxDepth: 2,
    );

    expect(iterator_to_array($filter->getIterator(), false))->toBe(['one', 'three']);
});

it('rejects negative maximum depth', function (): void {
    new RecursiveFilter(new StringFilter(), maxDepth: -1);
})->throws(InvalidArgumentException::class);

it('does not use deprecated SplObjectStorage traversal APIs', function (): void {
    $source = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            yield 'value' => 'accepted';
        }
    };

    set_error_handler(static function (int $severity, string $message): bool {
        if ($severity === E_DEPRECATED && str_contains($message, 'SplObjectStorage')) {
            throw new ErrorException($message);
        }

        return false;
    });

    try {
        $filter = new RecursiveFilter(new StringFilter(), iterable: $source);

        expect($filter->toArray())->toBe(['accepted']);
    } finally {
        restore_error_handler();
    }
});
