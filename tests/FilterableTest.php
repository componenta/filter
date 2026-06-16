<?php

declare(strict_types=1);

namespace Componenta\Filter\Tests;

use Componenta\Filter\CallbackFilter;
use Componenta\Filter\Filterable;
use Componenta\Filter\FilterableInterface;
use Componenta\Filter\FilterInterface;
use PHPUnit\Framework\TestCase;

final class FilterableTest extends TestCase
{
    public function testWithFilterAndWithoutFilterReturnNewInstances(): void
    {
        $filter = new CallbackFilter(static fn(mixed $value): bool => is_int($value));
        $filterable = new FilterableFixture();

        $withFilter = $filterable->withFilter($filter);
        $withoutFilter = $withFilter->withoutFilter($filter);

        self::assertNotSame($filterable, $withFilter);
        self::assertNotSame($withFilter, $withoutFilter);
        self::assertFalse($filterable->hasFilter($filter));
        self::assertTrue($withFilter->hasFilter($filter));
        self::assertFalse($withoutFilter->hasFilter($filter));
    }

    public function testAcceptRequiresEveryFilterToPass(): void
    {
        $filterable = (new FilterableFixture())
            ->withFilter(new CallbackFilter(static fn(mixed $value): bool => is_int($value)))
            ->withFilter(new CallbackFilter(static fn(mixed $value): bool => $value > 10));

        self::assertFalse($filterable->accept('11'));
        self::assertFalse($filterable->accept(5));
        self::assertTrue($filterable->accept(15));
    }

    public function testConstructorRejectsInvalidFilters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FilterableFixture(['not-a-filter']);
    }
}

final class FilterableFixture implements FilterableInterface
{
    use Filterable;

    /**
     * @param iterable<FilterInterface>|FilterInterface $filters
     */
    public function __construct(iterable|FilterInterface $filters = [])
    {
        $this->initFilters($filters);
    }
}
