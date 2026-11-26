<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('matches the independent BigInt oracle across exact divisibility boundaries and repeated calls', function (): void {
    // The optional fixture generator uses Node BigInt, independent of PHP's
    // decimal factoring, limb division, comparison and caching algorithms.
    $groups = json_decode(
        file_get_contents(__DIR__ . '/Fixtures/divisibility-vectors.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    foreach ($groups as $group) {
        $filter = new MultipleOfFilter($group['divisor']);
        $accepted = [];
        $values = [];

        foreach ($group['values'] as [$value, $expected]) {
            $message = sprintf('%s multiple of %s', $value, $group['divisor']);
            $values[] = $value;

            expect((new MultipleOfFilter($group['divisor']))->accept($value))->toBe($expected, $message)
                ->and($filter->accept($value))->toBe($expected, $message)
                ->and($filter->accept($value))->toBe($expected, $message);

            if ($expected) {
                $accepted[] = $value;
            }
        }

        expect($filter->withIterable($values)->toArray())->toBe($accepted);
    }
});
