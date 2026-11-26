<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\StringFilter;

it('defers aggregate initialization and iterator rewind until first consumption', function (): void {
    $iterator = new class([1, 'two']) extends ArrayIterator {
        public int $rewinds = 0;

        public function rewind(): void
        {
            $this->rewinds++;
            parent::rewind();
        }
    };
    $source = new class($iterator) implements IteratorAggregate {
        public int $opens = 0;
        public function __construct(private readonly Iterator $iterator) {}
        public function getIterator(): Traversable
        {
            $this->opens++;
            return $this->iterator;
        }
    };

    $filter = (new MergingFilter(new IntFilter(), new StringFilter()))->withIterable($source);
    $cursor = $filter->getIterator();

    expect($source->opens)->toBe(0)->and($iterator->rewinds)->toBe(0);
    expect(iterator_to_array($cursor, false))->toBe([1, 'two'])
        ->and($source->opens)->toBe(1)
        ->and($iterator->rewinds)->toBe(1)
        ->and($filter->toArray())->toBe([1, 'two'])
        ->and($source->opens)->toBe(1)
        ->and($iterator->rewinds)->toBe(1);
});

it('does not initialize an unused replay source', function (): void {
    $source = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            throw new LogicException('Must not open');
        }
    };

    expect((new MergingFilter())->withIterable($source)->toArray())->toBe([]);
});

it('replays initialization failures without reopening the aggregate', function (): void {
    $source = new class implements IteratorAggregate {
        public int $opens = 0;
        public function getIterator(): Traversable
        {
            $this->opens++;
            throw new RuntimeException('Opening failed');
        }
    };

    $filter = (new MergingFilter(new IntFilter()))->withIterable($source);

    expect(fn() => $filter->toArray())->toThrow(RuntimeException::class, 'Opening failed')
        ->and(fn() => $filter->toArray())->toThrow(RuntimeException::class, 'Opening failed')
        ->and($source->opens)->toBe(1);
});

it('bounds nonterminating replay source aggregate chains', function (): void {
    $source = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            return new self();
        }
    };

    $filter = (new MergingFilter(new IntFilter()))->withIterable($source);

    expect(fn() => $filter->toArray())->toThrow(OverflowException::class, 'Maximum replay source aggregate depth');
});
