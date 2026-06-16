<?php

declare(strict_types=1);

namespace Componenta\Filter\Tests;

use Componenta\Filter\StringFilter;
use PHPUnit\Framework\TestCase;

final class AbstractFilterTest extends TestCase
{
    public function testIteratesAcceptedValues(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'three';
            }
        };

        $filter = new StringFilter([
            'first' => 'one',
            'second' => 2,
            'third' => $stringable,
        ]);

        self::assertSame(['one', $stringable], $filter->toArray());
        self::assertSame(['first' => 'one', 'third' => $stringable], $filter->toArray(true));
    }

    public function testWithIterableReturnsNewFilter(): void
    {
        $filter = new StringFilter(['one']);
        $changed = $filter->withIterable([1, 'two']);

        self::assertNotSame($filter, $changed);
        self::assertSame(['one'], $filter->toArray());
        self::assertSame(['two'], $changed->toArray());
    }
}
