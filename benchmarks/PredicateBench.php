<?php

declare(strict_types=1);

namespace Componenta\Filter\Benchmarks;

use Componenta\Filter\BetweenFilter;
use Componenta\Filter\ChainableFilter;
use Componenta\Filter\DateRangeFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\IntFilter;
use Componenta\Filter\MultipleOfFilter;
use Componenta\Filter\RandomFilter;
use Componenta\Filter\RangeFilter;
use Componenta\Filter\RegexFilter;
use PhpBench\Attributes as Bench;
use Random\Engine\Mt19937;
use Random\Randomizer;

#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(2000)]
#[Bench\Iterations(5)]
final class PredicateBench
{
    private IntFilter $integer;
    private RangeFilter $range;
    private BetweenFilter $decimal;
    private GreaterThanFilter $hugeExponent;
    private MultipleOfFilter $multiple;
    private MultipleOfFilter $decimalMultiple;
    private ChainableFilter $chain;
    private RegexFilter $regex;
    private DateRangeFilter $date;
    private RandomFilter $random;

    public function setUp(): void
    {
        $this->integer = new IntFilter();
        $this->range = new RangeFilter(1, 100);
        $this->decimal = new BetweenFilter('0.10000000000000000001', '0.10000000000000000003');
        $this->hugeExponent = new GreaterThanFilter('1e100000000000000000000');
        $this->multiple = new MultipleOfFilter(7);
        $this->decimalMultiple = new MultipleOfFilter('0.000125');
        $this->decimalMultiple->accept('0.000375');
        $this->chain = ChainableFilter::create($this->integer, $this->range, $this->multiple);
        $this->regex = new RegexFilter('/^[a-z]+[0-9]+$/D');
        $this->date = new DateRangeFilter('2026-01-01', '2026-12-31', timezone: new \DateTimeZone('UTC'));
        $this->random = new RandomFilter(randomizer: new Randomizer(new Mt19937(42)));
    }

    public function benchIntPredicate(): void
    {
        $this->integer->accept(42);
    }

    public function benchIntegerRangeHot(): void
    {
        $this->range->accept(42);
    }

    public function benchDecimalRangeHot(): void
    {
        $this->decimal->accept('0.10000000000000000002');
    }

    public function benchDecimalRangeCold(): void
    {
        (new BetweenFilter('0.10000000000000000001', '0.10000000000000000003'))
            ->accept('0.10000000000000000002');
    }

    public function benchScientificComparison(): void
    {
        $this->hugeExponent->accept('2e100000000000000000000');
    }

    public function benchIntegerMultiple(): void
    {
        $this->multiple->accept(42);
    }

    public function benchDecimalMultipleHot(): void
    {
        $this->decimalMultiple->accept('0.00125');
    }

    public function benchDecimalMultipleCold(): void
    {
        (new MultipleOfFilter('0.000125'))->accept('0.00125');
    }

    public function benchCompositionAccepted(): void
    {
        $this->chain->accept(42);
    }

    public function benchCompositionShortCircuit(): void
    {
        $this->chain->accept('42');
    }

    public function benchRegex(): void
    {
        $this->regex->accept('example123');
    }

    public function benchDate(): void
    {
        $this->date->accept('2026-06-01T12:00:00.123456Z');
    }

    public function benchSeededRandom(): void
    {
        $this->random->accept(null);
    }
}
