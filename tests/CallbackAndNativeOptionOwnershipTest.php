<?php

declare(strict_types=1);

use Componenta\Filter\CallbackFilter;
use Componenta\Filter\FilterVarFilter;

it('validates and retains its own native filter option slots', function (): void {
    $max = 10;
    $flags = FILTER_NULL_ON_FAILURE;
    $native = ['max_range' => &$max];
    $options = ['flags' => &$flags, 'options' => &$native];

    $filter = new FilterVarFilter(FILTER_VALIDATE_INT, $options);
    $max = 100;
    $flags = 'invalid';
    $native = ['max_range' => 1000];

    expect($filter->accept('10'))->toBeTrue()
        ->and($filter->accept('11'))->toBeFalse()
        ->and($filter->getOptions())->toBe([
            'flags' => FILTER_NULL_ON_FAILURE,
            'options' => ['max_range' => 10],
        ]);

    $returned = $filter->getOptions();
    $returned['options']['max_range'] = 100;
    expect($filter->accept('11'))->toBeFalse();
});

it('retains the validated callable target but shares its intentional object state', function (): void {
    $target = new class {
        public bool $enabled = true;
        public function accept(mixed $value): bool
        {
            return $this->enabled && $value === 'yes';
        }
        public function reject(mixed $value): bool
        {
            return false;
        }
    };

    $method = 'accept';
    $callback = [$target, &$method];
    $direct = new CallbackFilter($callback);
    $native = new FilterVarFilter(FILTER_CALLBACK, ['options' => $callback]);

    $method = 'reject';

    expect($direct->accept('yes'))->toBeTrue()
        ->and($native->accept('yes'))->toBeTrue()
        ->and($direct->accept('no'))->toBeFalse()
        ->and($native->accept('no'))->toBeFalse();

    $target->enabled = false;
    expect($direct->accept('yes'))->toBeFalse()
        ->and($native->accept('yes'))->toBeFalse();
});
