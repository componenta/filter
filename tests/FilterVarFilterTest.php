<?php

declare(strict_types=1);

namespace Componenta\Filter\Tests;

use Componenta\Filter\FilterVarFilter;
use PHPUnit\Framework\TestCase;

final class FilterVarFilterTest extends TestCase
{
    public function testBooleanValidationDistinguishesFalseFromFailure(): void
    {
        $filter = new FilterVarFilter(FILTER_VALIDATE_BOOLEAN);

        self::assertTrue($filter->accept(false));
        self::assertTrue($filter->accept('false'));
        self::assertFalse($filter->accept('not-a-boolean'));
    }
}
