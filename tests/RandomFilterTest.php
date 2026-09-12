<?php

declare(strict_types=1);

use Componenta\Filter\RandomFilter;
use Random\Engine;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class RandomFilterMutableState
{
    public int $counter = 0;
}

final class RandomFilterNestedStateEngine implements Engine
{
    public function __construct(
        public RandomFilterMutableState $state = new RandomFilterMutableState(),
    ) {
    }

    public function generate(): string
    {
        return $this->state->counter++ === 0
            ? str_repeat("\0", 8)
            : str_repeat("\xFF", 8);
    }
}

dataset('invalid probabilities', [
    'below zero' => -0.1,
    'above one' => 1.1,
    'NaN' => NAN,
    'positive infinity' => INF,
    'negative infinity' => -INF,
]);

it('rejects invalid probability configuration', function (float $probability): void {
    new RandomFilter($probability);
})->with('invalid probabilities')->throws(InvalidArgumentException::class);

it('has deterministic behavior at probability boundaries', function (): void {
    $never = new RandomFilter(0.0);
    $always = new RandomFilter(1.0);

    foreach (range(1, 100) as $value) {
        expect($never->accept($value))->toBeFalse()
            ->and($always->accept($value))->toBeTrue();
    }
});

it('does not consume RNG state at deterministic probability boundaries', function (float $probability, bool $expected): void {
    $seed = 123456;
    $randomizer = new Randomizer(new Mt19937($seed));
    $control = new Randomizer(new Mt19937($seed));
    $filter = new RandomFilter($probability, randomizer: $randomizer);

    expect($filter->accept('value'))->toBe($expected)
        ->and($randomizer->nextFloat())->toBe($control->nextFloat());
})->with([
    'never' => [0.0, false],
    'always' => [1.0, true],
]);

it('uses a strict probability boundary', function (): void {
    $seed = 42;
    $control = new Randomizer(new Mt19937($seed));
    $probability = $control->nextFloat();
    $filter = new RandomFilter(
        $probability,
        randomizer: new Randomizer(new Mt19937($seed)),
    );

    expect($filter->accept('exact boundary'))->toBeFalse();
});

it('honors iterable binding at deterministic probability boundaries', function (): void {
    expect((new RandomFilter(0.0, [1, 2, 3]))->toArray())->toBe([])
        ->and((new RandomFilter(1.0, [1, 2, 3]))->toArray())->toBe([1, 2, 3]);
});

it('does not mutate the process-global mt_rand state', function (): void {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    $code = sprintf(<<<'PHP'
require %s;

use Componenta\Filter\RandomFilter;

mt_srand(123456);
$expected = [mt_rand(), mt_rand()];

mt_srand(123456);
$actual = [mt_rand()];
(new RandomFilter(0.5))->accept('value');
$actual[] = mt_rand();

echo json_encode([$expected, $actual], JSON_THROW_ON_ERROR);
PHP, var_export($autoload, true));

    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, '-r', $code],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );

    expect($process)->not->toBeFalse();

    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    expect($exitCode)->toBe(0, $error);

    [$expected, $actual] = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

    expect($actual)->toBe($expected);
});

it('supports an injected deterministic Randomizer', function (): void {
    $seed = 42;
    $control = new Randomizer(new Mt19937($seed));
    $filter = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937($seed)),
    );

    $expected = [];
    $actual = [];

    foreach (range(1, 100) as $value) {
        $expected[] = $control->nextFloat() < 0.5;
        $actual[] = $filter->accept($value);
    }

    expect($actual)->toBe($expected)
        ->and(array_unique($actual))->toHaveCount(2);
});

it('does not share RNG state with an immutable iterable clone', function (): void {
    $seed = 2;
    $control = new Randomizer(new Mt19937($seed));
    $expectedFirst = $control->nextFloat() < 0.5;
    $original = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937($seed)),
    );
    $changed = $original->withIterable([1, 2, 3]);

    $changed->accept('consume clone RNG');

    expect($original->accept('original'))->toBe($expectedFirst);
});

it('deep-copies nested custom engine state for immutable clones', function (): void {
    $original = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new RandomFilterNestedStateEngine()),
    );
    $changed = $original->withIterable([1, 2, 3]);

    expect($changed->accept('consume clone RNG'))->toBeTrue()
        ->and($original->accept('original still starts at first sample'))->toBeTrue()
        ->and($changed->accept('clone advances independently'))->toBeFalse()
        ->and($original->accept('original advances independently'))->toBeFalse();
});

it('does not share RNG state with a probability clone', function (): void {
    $seed = 2;
    $control = new Randomizer(new Mt19937($seed));
    $expectedFirst = $control->nextFloat() < 0.5;
    $original = new RandomFilter(
        0.5,
        randomizer: new Randomizer(new Mt19937($seed)),
    );
    $changed = $original->withProbability(0.5);

    $changed->accept('consume clone RNG');

    expect($original->accept('original'))->toBe($expectedFirst);
});

it('keeps default secure-engine clones usable through immutable updates', function (): void {
    $filter = new RandomFilter(0.5);

    $changed = $filter
        ->withIterable([1, 2, 3])
        ->withProbability(1.0);

    expect($changed->toArray())->toBe([1, 2, 3]);
});

it('fails fast when an injected engine cannot be copied immutably', function (): void {
    $engine = new class implements Engine {
        private function __clone() {}

        public function generate(): string
        {
            return random_bytes(8);
        }
    };

    $filter = new RandomFilter(
        0.5,
        randomizer: new Randomizer($engine),
    );

    expect(fn() => $filter->withIterable([1, 2, 3]))
        ->toThrow(LogicException::class, 'cannot be copied immutably')
        ->and(fn() => $filter->withProbability(0.25))
        ->toThrow(LogicException::class, 'cannot be copied immutably');
});
