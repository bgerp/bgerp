<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\CoverageCalculator;

use ImageColorAnalyzer\Contracts\ClusterResult;
use ImageColorAnalyzer\Contracts\ColorCoverage;
use ImageColorAnalyzer\Contracts\CoverageCalculatorInterface;

/**
 * OWNER: Developer C.
 *
 * Turns a {@see ClusterResult} into a sorted list of {@see ColorCoverage}. Each
 * percentage is `cluster weight / totalAnalyzedPixels * 100`, rounded to one
 * decimal with the **largest-remainder method** so the displayed values sum to
 * exactly 100.0 (no "99.9%" artifacts from independent rounding).
 *
 * The math is done in integer tenths of a percent: every cluster gets its
 * floored share, then the leftover tenths (there are always fewer than the
 * cluster count) go to the clusters with the largest fractional remainder.
 */
final class PercentageCoverageCalculator implements CoverageCalculatorInterface
{
    /** Total budget expressed in tenths of a percent (100.0% == 1000 tenths). */
    private const TENTHS_TOTAL = 1000;

    public function calculate(ClusterResult $result): array
    {
        $total = $result->totalAnalyzedPixels;
        if ($total === 0 || $result->clusters === []) {
            return [];
        }

        $tenths = [];
        $remainders = [];
        $allocated = 0;
        foreach ($result->clusters as $i => $cluster) {
            $exact = $cluster->weight / $total * self::TENTHS_TOTAL;
            $floor = (int) floor($exact);
            $tenths[$i] = $floor;
            $remainders[$i] = $exact - $floor;
            $allocated += $floor;
        }

        $this->distributeRemainder($tenths, $remainders, self::TENTHS_TOTAL - $allocated, $result);

        $coverages = [];
        foreach ($result->clusters as $i => $cluster) {
            $coverages[] = new ColorCoverage(
                $cluster->centroid->toHex(),
                $cluster->centroid->toRgbTriplet(),
                $tenths[$i] / 10.0,
            );
        }

        usort($coverages, static function (ColorCoverage $a, ColorCoverage $b): int {
            return $b->coveragePercent <=> $a->coveragePercent ?: strcmp($a->color, $b->color);
        });

        return $coverages;
    }

    /**
     * Hands the $leftover tenths to the clusters with the largest fractional
     * remainder (ties broken by larger weight, then lower hex) so the total lands
     * on exactly 1000 tenths.
     *
     * @param array<int, int>   $tenths     modified in place
     * @param array<int, float> $remainders
     */
    private function distributeRemainder(array &$tenths, array $remainders, int $leftover, ClusterResult $result): void
    {
        if ($leftover <= 0) {
            return;
        }

        $order = array_keys($remainders);
        usort($order, static function (int $a, int $b) use ($remainders, $result): int {
            $byRemainder = $remainders[$b] <=> $remainders[$a];
            if ($byRemainder !== 0) {
                return $byRemainder;
            }
            $byWeight = $result->clusters[$b]->weight <=> $result->clusters[$a]->weight;
            if ($byWeight !== 0) {
                return $byWeight;
            }

            return strcmp($result->clusters[$a]->centroid->toHex(), $result->clusters[$b]->centroid->toHex());
        });

        for ($i = 0; $i < $leftover; $i++) {
            $tenths[$order[$i]]++;
        }
    }
}
