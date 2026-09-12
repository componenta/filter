<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('does not mistake nonzero float quotient underflow for a multiple', function (): void {
    $filter = new MultipleOfFilter('1e10000');

    expect($filter->accept(1.0))->toBeFalse()
        ->and($filter->accept(-1.0))->toBeFalse();
});
