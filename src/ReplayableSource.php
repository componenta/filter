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

        if ($this->replayable === null) {
            $this->initialize();
        }

        return $this->replayable->cursor();
    }

    private function initialize(): void
    {
        if ($this->initializing) {
            throw new \LogicException('Replay source initialization is already in progress');
        }

        $this->initializing = true;

        try {
            $source = $this->source;
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

            $this->replayable = new ReplayableIterator($source);
            $this->source = null;
        } catch (\Throwable $failure) {
            $this->failure = $failure;
            $this->source = null;
            throw $failure;
        } finally {
            if ($this->replayable === null && $this->failure === null) {
                $this->failure = new \LogicException('Replay source initialization was interrupted');
                $this->source = null;
            }

            $this->initializing = false;
        }
    }
}
