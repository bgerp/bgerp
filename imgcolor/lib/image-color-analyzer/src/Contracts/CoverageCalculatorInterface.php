<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

interface CoverageCalculatorInterface
{
    /**
     * @return list<ColorCoverage> sorted by coverage descending; percentages sum to 100.0
     */
    public function calculate(ClusterResult $result): array;
}
