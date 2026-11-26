<?php

declare(strict_types=1);

namespace Componenta\Filter\Benchmarks;

use Componenta\Filter\ArrayDiffFilter;
use Componenta\Filter\ArrayIntersectFilter;
use Componenta\Filter\FileExtensionFilter;
use Componenta\Filter\KeyInFilter;
use Componenta\Filter\StringEqualsAnyFilter;
use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
#[Bench\ParamProviders('provideSizes')]
#[Bench\Revs(50)]
#[Bench\Iterations(5)]
final class MembershipBench
{
    private array $values;
    private array $misses;
    private StringEqualsAnyFilter $strings;
    private FileExtensionFilter $extensions;
    private KeyInFilter $keys;
    private ArrayIntersectFilter $intersect;
    private ArrayDiffFilter $diff;

    public function provideSizes(): iterable
    {
        yield 'small' => ['size' => 10];
        yield 'large' => ['size' => 1000];
    }

    public function setUp(array $params): void
    {
        $this->values = array_map(static fn(int $i): string => 'item-' . $i, range(1, $params['size']));
        $this->misses = array_map(static fn(int $i): string => 'miss-' . $i, range(1, 100));
        $this->strings = new StringEqualsAnyFilter($this->values, false);
        $this->extensions = new FileExtensionFilter($this->values);
        $this->keys = new KeyInFilter($this->values);
        $this->intersect = new ArrayIntersectFilter($this->values);
        $this->diff = new ArrayDiffFilter($this->values);
    }

    public function benchStringMatchHot(array $params): void
    {
        $this->strings->accept('ITEM-' . $params['size']);
    }

    public function benchStringMatchCold(array $params): void
    {
        (new StringEqualsAnyFilter($this->values, false))->accept('ITEM-' . $params['size']);
    }

    public function benchFileExtension(array $params): void
    {
        $this->extensions->accept('file.ITEM-' . $params['size']);
    }

    public function benchKey(array $params): void
    {
        $this->keys->accept(null, 'item-' . $params['size']);
    }

    public function benchIntersectionMiss(array $params): void
    {
        $this->intersect->accept($this->misses);
    }

    public function benchDifferenceMiss(array $params): void
    {
        $this->diff->accept($this->misses);
    }
}
