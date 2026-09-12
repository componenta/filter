<?php

declare(strict_types=1);

use Componenta\Filter\PropertyEqualsFilter;

it('rejects every non-object value without touching property metadata', function (): void {
    $filter = new PropertyEqualsFilter('status', 'active');

    expect($filter->accept(null))->toBeFalse()
        ->and($filter->accept(0))->toBeFalse()
        ->and($filter->accept('active'))->toBeFalse()
        ->and($filter->accept([]))->toBeFalse();
});
