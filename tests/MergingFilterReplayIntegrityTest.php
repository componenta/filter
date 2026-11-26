<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;

it('rejects reentrant reads during source consumption without corrupting the shared cache', function (): void {
    $source = new class([1, 2]) extends ArrayIterator {
        public ?Closure $onRead = null;

        public function current(): mixed
        {
            $value = parent::current();

            if ($this->onRead !== null) {
                $callback = $this->onRead;
                $this->onRead = null;
                $callback();
            }

            return $value;
        }
    };

    $merge = (new MergingFilter(new IntFilter()))->withIterable($source);
    $rejected = false;
    $source->onRead = static function () use ($merge, &$rejected): void {
        try {
            $merge->toArray();
        } catch (LogicException) {
            $rejected = true;
        }
    };

    expect($merge->toArray())->toBe([1, 2])
        ->and($rejected)->toBeTrue()
        ->and($merge->toArray())->toBe([1, 2]);
});

it('never treats an interrupted fiber source read as a successful traversal', function (): void {
    $source = new class([1, 2]) extends ArrayIterator {
        private bool $suspended = false;

        public function current(): mixed
        {
            if (!$this->suspended) {
                $this->suspended = true;
                Fiber::suspend('source read');
            }

            return parent::current();
        }
    };

    $merge = (new MergingFilter(new IntFilter()))->withIterable($source);
    $fiber = new Fiber(static fn(): array => $merge->toArray());

    expect($fiber->start())->toBe('source read')
        ->and($fiber->isSuspended())->toBeTrue();

    // Concurrent uncached reads cannot corrupt the source position.
    expect(fn() => $merge->toArray())->toThrow(LogicException::class);

    unset($fiber);

    // Destruction of a suspended Fiber interrupts the read. Subsequent
    // traversals must consistently report failure, never a partial cache.
    expect(fn() => $merge->toArray())->toThrow(LogicException::class, 'Source read was interrupted')
        ->and(fn() => $merge->toArray())->toThrow(LogicException::class, 'Source read was interrupted');
});

it('preserves source failure identity when releasing the failed iterator also throws', function (): void {
    $failure = new RuntimeException('source failed');

    $merge = (new MergingFilter(new IntFilter()))->withIterable(new class($failure) extends ArrayIterator {
        public function __construct(private readonly RuntimeException $failure)
        {
            parent::__construct([1]);
        }

        public function current(): mixed
        {
            throw $this->failure;
        }

        public function __destruct()
        {
            throw new LogicException('release failed');
        }
    });

    for ($i = 0; $i < 2; $i++) {
        $observed = null;

        try {
            $merge->toArray();
        } catch (Throwable $exception) {
            $observed = $exception;
        }

        expect($observed)->toBe($failure);
    }
});
