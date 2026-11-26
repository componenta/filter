<?php

declare(strict_types=1);

use Random\Engine\Mt19937;
use Random\Randomizer;

it('retains the canonical decimal contract when precision INI functions are disabled', function (string $disabled): void {
    if (!function_exists('proc_open')) {
        $this->markTestSkipped('Requires a separate PHP process to configure disable_functions');
    }

    $values = [0.0, -0.0, 0.1, -0.1, PHP_FLOAT_MIN, PHP_FLOAT_MAX, -PHP_FLOAT_MAX,
        5.0e-324, 7.120236347223045e-307, 8.209073602596753e-289,
        9007199254740992.0, 1000000000000000128.0, 1.2345678901234567];

    // Powers of two have asymmetric rounding boundaries and are particularly
    // useful for distinguishing the shortest representation from %.17h.
    for ($exponent = -1074; $exponent <= 1023; $exponent += 11) {
        $values[] = 2.0 ** $exponent;
        $values[] = -(2.0 ** $exponent);
    }

    $random = new Randomizer(new Mt19937(20261126));

    for ($i = 0; $i < 256; $i++) {
        $value = unpack('d', $random->getBytes(8))[1];

        if (is_finite($value)) {
            $values[] = $value;
        }
    }

    $previous = ini_set('serialize_precision', '-1');

    if ($previous === false) {
        $this->markTestSkipped('The parent process must supply an independent native JSON oracle');
    }

    try {
        $vectors = array_map(
            static fn(float $value): array => [
                bin2hex(pack('d', $value)),
                json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
            ],
            $values,
        );
    } finally {
        ini_set('serialize_precision', $previous);
    }

    $process = proc_open(
        [PHP_BINARY, '-d', 'disable_functions=' . $disabled, '-d', 'serialize_precision=3',
            __DIR__ . '/Fixtures/float-decimal-restricted.php'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );

    expect($process)->toBeResource();

    fwrite($pipes[0], json_encode($vectors, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    expect(proc_close($process))->toBe(0, $errors)
        ->and(trim($output))->toBe((string) count($vectors))
        ->and($errors)->toBe('');
})->with(['ini_set', 'ini_get', 'ini_get,ini_set']);
