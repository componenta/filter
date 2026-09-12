<?php

declare(strict_types=1);

use Componenta\Filter\RegexFilter;

it('rejects an invalid regular expression at construction time', function (): void {
    new RegexFilter('/[');
})->throws(InvalidArgumentException::class);

it('rejects invalid patterns without leaking preg_match warnings', function (): void {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    $code = sprintf(<<<'PHP'
require %s;

use Componenta\Filter\RegexFilter;

error_clear_last();

try {
    new RegexFilter('/[');
    $exception = null;
} catch (Throwable $throwable) {
    $exception = $throwable::class;
}

echo json_encode([$exception, error_get_last()], JSON_THROW_ON_ERROR);
PHP, var_export($autoload, true));

    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, '-d', 'display_errors=1', '-r', $code],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );

    expect($process)->not->toBeFalse();

    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    expect($exitCode)->toBe(0, $error)
        ->and($error)->toBe('');

    [$exception, $lastError] = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

    expect($exception)->toBe(InvalidArgumentException::class)
        ->and($lastError)->toBeNull();
});

it('continues to match a valid regular expression', function (): void {
    $filter = new RegexFilter('/^foo\d+$/');

    expect($filter->accept('foo42'))->toBeTrue()
        ->and($filter->accept('bar42'))->toBeFalse();
});
