<?php

declare(strict_types=1);

namespace Componenta\Filter\Tests;

use Componenta\Filter\BetweenFilter;
use Componenta\Filter\CallbackFilter;
use Componenta\Filter\StringFilter;
use PHPUnit\Framework\TestCase;

final class ScalarFiltersTest extends TestCase
{
    public function testStringFilterAcceptsStringsAndStringableValues(): void
    {
        $filter = new StringFilter();

        self::assertTrue($filter->accept('value'));
        self::assertTrue($filter->accept(new class implements \Stringable {
            public function __toString(): string
            {
                return 'value';
            }
        }));
        self::assertFalse($filter->accept(10));
    }

    public function testBetweenFilterSupportsInclusiveAndExclusiveBounds(): void
    {
        $inclusive = new BetweenFilter(1, 3);
        $exclusive = new BetweenFilter(1, 3, inclusive: false);

        self::assertTrue($inclusive->accept(1));
        self::assertTrue($inclusive->accept(2));
        self::assertTrue($inclusive->accept(3));
        self::assertFalse($exclusive->accept(1));
        self::assertTrue($exclusive->accept(2));
        self::assertFalse($exclusive->accept(3));
        self::assertFalse($inclusive->accept('not numeric'));
    }

    public function testCallbackFilterReceivesValueAndKey(): void
    {
        $filter = new CallbackFilter(
            static fn(mixed $value, string|int|null $key): bool => $key === 'enabled' && $value === true,
        );

        self::assertTrue($filter->accept(true, 'enabled'));
        self::assertFalse($filter->accept(true, 'disabled'));
    }
}
