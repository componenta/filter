<?php

declare(strict_types=1);

namespace Componenta\Filter\Benchmarks;

use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\PercentageFilter;
use Componenta\Filter\RecursiveFilter;
use Componenta\Filter\StringFilter;
use Componenta\Filter\UniqueFilter;
use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
#[Bench\ParamProviders('provideSizes')]
#[Bench\Revs(1)]
#[Bench\Iterations(5)]
final class CollectionBench
{
    private array $values;
    private array $mixed;
    private array $duplicates;
    private MergingFilter $merged;

    public function provideSizes(): iterable
    {
        yield 'small' => ['size' => 100];
        yield 'medium' => ['size' => 1000];
        yield 'large' => ['size' => 10000];
    }

    public function setUp(array $params): void
    {
        $this->values = range(1, $params['size']);
        $this->mixed = [];
        $this->duplicates = [];

        foreach ($this->values as $value) {
            $this->mixed[] = $value;
            $this->mixed[] = (string) $value;
            $this->duplicates[] = $value;
            $this->duplicates[] = $value;
        }

        $this->merged = (new MergingFilter(new IntFilter(), new StringFilter()))
            ->withIterable(self::generator($this->mixed));
        $this->merged->toArray();
    }

    public function benchArrayFilter(): void
    {
        (new IntFilter($this->mixed))->toArray();
    }

    public function benchGeneratorFilter(): void
    {
        (new IntFilter(self::generator($this->mixed)))->toArray();
    }

    public function benchUniqueDistinct(): void
    {
        (new UniqueFilter($this->values))->toArray();
    }

    public function benchUniqueDuplicates(): void
    {
        (new UniqueFilter($this->duplicates))->toArray();
    }

    public function benchPercentageArray(): void
    {
        (new PercentageFilter(29.5, $this->values))->toArray();
    }

    public function benchPercentageGenerator(): void
    {
        (new PercentageFilter(29.5, self::generator($this->values)))->toArray();
    }

    public function benchRecursive(): void
    {
        (new RecursiveFilter(new IntFilter(), iterable: [$this->mixed, [$this->mixed]]))->toArray();
    }

    public function benchReplayCold(): void
    {
        (new MergingFilter(new IntFilter(), new StringFilter()))
            ->withIterable(self::generator($this->mixed))
            ->toArray();
    }

    public function benchReplayHot(): void
    {
        $this->merged->toArray();
    }

    private static function generator(array $values): \Generator
    {
        yield from $values;
    }
}
