<?php

declare(strict_types=1);

use Componenta\Filter\AbstractCollectionFilter;
use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;

it('does not open a replay source when a custom filter obtains but has not consumed its cursor', function (): void {
    $source = new class implements IteratorAggregate {
        public int $opens = 0;

        public function getIterator(): Traversable
        {
            $this->opens++;
            return new ArrayIterator([1, 2]);
        }
    };

    $inner = new class extends AbstractCollectionFilter {
        public function withIterable(iterable $iterable): static
        {
            return parent::withIterable($iterable instanceof IteratorAggregate ? $iterable->getIterator() : $iterable);
        }

        public function getIterator(): Generator
        {
            yield from $this->iterable;
        }
    };

    $filter = (new MergingFilter($inner, new IntFilter()))->withIterable($source);
    expect($source->opens)->toBe(0)
        ->and($filter->toArray())->toBe([1, 2, 1, 2])
        ->and($source->opens)->toBe(1);
});

it('preserves the first initialization failure even if releasing the source throws', function (): void {
    $filter = (new MergingFilter(new IntFilter()))->withIterable(new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            throw new RuntimeException('Opening failed');
        }

        public function __destruct()
        {
            throw new LogicException('Release failed');
        }
    });

    expect(fn() => $filter->toArray())->toThrow(RuntimeException::class, 'Opening failed')
        ->and(fn() => $filter->toArray())->toThrow(RuntimeException::class, 'Opening failed');
});

it('replays a failure while releasing a successfully opened aggregate', function (): void {
    $filter = (new MergingFilter(new IntFilter()))->withIterable(new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            return new ArrayIterator([1]);
        }

        public function __destruct()
        {
            throw new LogicException('Release failed');
        }
    });

    expect(fn() => $filter->toArray())->toThrow(LogicException::class, 'Release failed')
        ->and(fn() => $filter->toArray())->toThrow(LogicException::class, 'Release failed');
});
