<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('does not enable integer validation flags implicitly', function (): void {
    $defaultOptions = new FilterVarFilter(FILTER_VALIDATE_INT);
    $arrayOptionsWithoutFlags = new FilterVarFilter(
        FILTER_VALIDATE_INT,
        ['options' => []],
    );
    $explicitOctal = new FilterVarFilter(
        FILTER_VALIDATE_INT,
        FILTER_FLAG_ALLOW_OCTAL,
    );

    expect($defaultOptions->accept('0755'))->toBeFalse()
        ->and($arrayOptionsWithoutFlags->accept('0755'))->toBeFalse()
        ->and($explicitOctal->accept('0755'))->toBeTrue();
});
