<?php

declare(strict_types=1);

namespace Componenta\Filter;

use Componenta\Stdlib\ReplayableIterator;

/**
 * Defers the initialization of a shared replay cache until its first read.
 *
 * @internal
 */
final class ReplayableSource implements \IteratorAggregate
{
    private ?ReplayableIterator $replayable = null;
    private ?\Throwable $failure = null;
    private bool $initializing = false;

    public function __construct(private ?\Traversable $source)
    {
    }

    public function getIterator(): \Traversable
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        if ($this->initializing) {
            throw new \LogicException('Replay source initialization is already in progress');
        }

        if ($this->replayable === null) {
            $this->initialize();
        }

        yield from $this->replayable->cursor();
    }

    private function initialize(): void
    {
        if ($this->initializing) {
            throw new \LogicException('Replay source initialization is already in progress');
        }

        $this->initializing = true;

        try {
            $this->replayable = self::createReplayable($this->source);
            $this->source = null;
        } catch (\Throwable $failure) {
            $this->failure = $failure;

            try {
                $this->source = null;
                $this->replayable = null;
            } finally {
                throw $failure;
            }
        } finally {
            if ($this->replayable === null && $this->failure === null) {
                $this->failure = new \LogicException('Replay source initialization was interrupted');
                try {
                    $this->source = null;
                } catch (\Throwable) {
                    // Preserve the interruption as the primary failure.
                }
            }

            $this->initializing = false;
        }
    }

    private static function createReplayable(\Traversable $source): ReplayableIterator
    {
        $seen = new \SplObjectStorage();
        // The underlying iterator also detects identity cycles but does not
        // bound chains that return a fresh aggregate at every step.
        while ($source instanceof \IteratorAggregate) {
            if ($seen->offsetExists($source)) {
                throw new \RuntimeException('IteratorAggregate cycle detected');
            }

            if (count($seen) >= 64) {
                throw new \OverflowException('Maximum replay source aggregate depth of 64 exceeded');
            }

            $seen->offsetSet($source, null);
            $source = $source->getIterator();
        }

        return new ReplayableIterator($source);
    }
}
