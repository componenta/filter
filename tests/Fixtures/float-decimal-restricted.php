<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;
use Componenta\Filter\PercentageFilter;
use Componenta\Filter\RangeFilter;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$vectors = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);

foreach ($vectors as [$hex, $decimal]) {
    $value = unpack('d', hex2bin($hex))[1];

    $range = new RangeFilter($decimal, $decimal);

    if (!$range->accept($value)
        || !$range->accept($value)
        || !(new RangeFilter($value, $value))->accept($decimal)
        || ($value != 0.0 && !(new MultipleOfFilter($value))->accept($decimal))
    ) {
        fwrite(STDERR, sprintf("Round-trip decimal mismatch: %s (%s)\n", $hex, $decimal));
        exit(1);
    }
}

$values = range(1, 1000);

foreach ([
    [0.1, 1],
    [0.09999999999999999, 0],
    [29.499999999999996, 294],
    [29.5, 295],
    [29.500000000000004, 295],
] as [$percentage, $expected]) {
    if (count((new PercentageFilter($percentage, $values))->toArray()) !== $expected) {
        fwrite(STDERR, "Percentage mismatch in restricted precision environment\n");
        exit(1);
    }
}

if (function_exists('ini_get') && ini_get('serialize_precision') !== '3') {
    fwrite(STDERR, "Serialization precision leaked\n");
    exit(1);
}

echo count($vectors), PHP_EOL;
