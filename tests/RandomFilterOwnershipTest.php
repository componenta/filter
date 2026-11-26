<?php

declare(strict_types=1);

use Componenta\Filter\RandomFilter;
use Random\Engine;
use Random\Engine\Mt19937;
use Random\Randomizer;

it('treats an explicitly injected randomizer as a live shared collaborator', function (): void {
    $randomizer = new Randomizer(new Mt19937(42));
    $control = new Randomizer(new Mt19937(42));
    $filter = new RandomFilter(randomizer: $randomizer);

    expect($filter->getRandomizer())->toBe($randomizer)
        ->and($filter->getRandomizer()->nextFloat())->toBe($control->nextFloat())
        ->and($filter->accept(null))->toBe($control->nextFloat() < 0.5);

    $changed = $filter->withIterable([1, 2, 3]);
    expect($changed->getRandomizer())->not->toBe($randomizer)
        ->and($changed->accept(null))->toBe($filter->accept(null));
});

it('reports a failing custom engine copy as a configuration-copy error', function (): void {
    $engine = new class implements Engine {
        public function generate(): string
        {
            return str_repeat("\0", 8);
        }

        public function __clone(): void
        {
            throw new RuntimeException('Copy rejected by engine');
        }
    };
    $filter = new RandomFilter(randomizer: new Randomizer($engine));

    try {
        $filter->withProbability(0.25);
        throw new RuntimeException('Expected copy to fail');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toContain('cannot be copied immutably')
            ->and($exception->getPrevious())->toBeInstanceOf(RuntimeException::class);
    }

    expect($filter->accept('value'))->toBeTrue();
});
