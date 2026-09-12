<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('uses bounded tolerance when a float is one ulp beyond the reconstructed multiple', function (): void {
    $filter = new MultipleOfFilter(0.1);

    expect($filter->accept(0.3000000000000001))->toBeTrue()
        ->and($filter->accept(-0.3000000000000001))->toBeTrue()
        ->and($filter->accept(0.30000000000001))->toBeFalse();
});
