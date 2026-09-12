<?php

declare(strict_types=1);

use Componenta\Filter\FilterVarFilter;

it('does not let defaults mask failure for every validation filter', function (int $filter, mixed $invalid, array $options = [], int $flags = 0): void {
    $filterOptions = $options;
    $filterOptions['default'] = 'fallback';

    $subject = new FilterVarFilter(
        $filter,
        [
            'flags' => $flags,
            'options' => $filterOptions,
        ],
    );

    expect($subject->accept($invalid))->toBeFalse();
})->with([
    'integer' => [FILTER_VALIDATE_INT, 'invalid'],
    'boolean' => [FILTER_VALIDATE_BOOLEAN, 'invalid'],
    'float' => [FILTER_VALIDATE_FLOAT, 'invalid'],
    'regexp' => [FILTER_VALIDATE_REGEXP, 'bar', ['regexp' => '/^foo$/']],
    'domain' => [FILTER_VALIDATE_DOMAIN, 'bad domain', [], FILTER_FLAG_HOSTNAME],
    'url' => [FILTER_VALIDATE_URL, 'not-a-url'],
    'email' => [FILTER_VALIDATE_EMAIL, 'not-an-email'],
    'ip' => [FILTER_VALIDATE_IP, '999.1.1.1'],
    'mac' => [FILTER_VALIDATE_MAC, 'not-a-mac'],
]);
