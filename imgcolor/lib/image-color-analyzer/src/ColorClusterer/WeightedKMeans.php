<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ColorClusterer;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * OWNER: Developer C.
 *
 * Deterministic weighted k-means with k-means++ seeding, operating on points in
 * CIELAB. Shared by {@see KSelector} (to score candidate k values) and
 * {@see KMeansClusterer} (for the final clustering) so the two never disagree.
 *
 * Distances are squared-Euclidean in Lab. Because CIE76 delta-E *is* Euclidean
 * distance in Lab, the squared form yields identical nearest-centroid
 * assignments while avoiding a sqrt in the hot loop.
 *
 * Determinism: a local {@see Mt19937} engine seeded per call (never the global
 * mt_rand state), plus lowest-index tie-breaking, makes the output a pure
 * function of (points, weights, k, seed).
 */
final class WeightedKMeans
{
    /** Safety cap on Lloyd iterations; assignments almost always stabilize well before this. */
    public const MAX_ITERATIONS = 100;

    /**
     * @param list<array{0:float,1:float,2:float}> $points  Lab points to cluster
     * @param list<int>                            $weights per-point weight (pixel count); same index as $points
     *
     * @return array{centroids: list<array{0:float,1:float,2:float}>, assignments: list<int>}
     *         `assignments[i]` is the centroid index for `points[i]`.
     */
    public function run(array $points, array $weights, int $k, int $seed, int $maxIterations = self::MAX_ITERATIONS): array
    {
        $n = count($points);
        $k = max(1, min($k, $n));

        if ($n === 0) {
            return ['centroids' => [], 'assignments' => []];
        }

        $centroids = $this->initializePlusPlus($points, $weights, $k, $seed);

        /** @var list<int> $assignments */
        $assignments = array_fill(0, $n, -1);

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $changed = false;

            foreach ($points as $i => $point) {
                $best = 0;
                $bestDistance = $this->distanceSq($point, $centroids[0]);
                for ($c = 1; $c < $k; $c++) {
                    $distance = $this->distanceSq($point, $centroids[$c]);
                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $best = $c;
                    }
                }
                if ($assignments[$i] !== $best) {
                    $assignments[$i] = $best;
                    $changed = true;
                }
            }

            if (!$changed && $iteration > 0) {
                break;
            }

            $centroids = $this->recomputeCentroids($points, $weights, $assignments, $centroids);
        }

        return ['centroids' => $centroids, 'assignments' => $assignments];
    }

    /**
     * Weighted within-cluster sum of squares (the elbow/WCSS diagnostic).
     *
     * @param list<array{0:float,1:float,2:float}> $points
     * @param list<int>                            $weights
     * @param list<array{0:float,1:float,2:float}> $centroids
     * @param list<int>                            $assignments
     */
    public function wcss(array $points, array $weights, array $centroids, array $assignments): float
    {
        $sum = 0.0;
        foreach ($points as $i => $point) {
            $sum += $weights[$i] * $this->distanceSq($point, $centroids[$assignments[$i]]);
        }

        return $sum;
    }

    /**
     * @param list<array{0:float,1:float,2:float}> $points
     * @param list<int>                            $weights
     *
     * @return list<array{0:float,1:float,2:float}>
     */
    private function initializePlusPlus(array $points, array $weights, int $k, int $seed): array
    {
        $randomizer = new Randomizer(new Mt19937($seed));
        $n = count($points);

        /** @var list<int> $chosen indices already picked as centroids */
        $chosen = [];

        // First centroid: weighted-random by pixel count.
        $totalWeight = array_sum($weights);
        $chosen[] = $this->sampleByCumulativeInt($weights, $randomizer->getInt(0, max(0, $totalWeight - 1)));

        while (count($chosen) < $k) {
            // D^2: min squared distance from each point to the nearest chosen centroid,
            // weighted by pixel count (k-means++ selection distribution).
            $weightedD2 = [];
            $sum = 0.0;
            foreach ($points as $i => $point) {
                $min = INF;
                foreach ($chosen as $centroidIndex) {
                    $distance = $this->distanceSq($point, $points[$centroidIndex]);
                    if ($distance < $min) {
                        $min = $distance;
                    }
                }
                $value = $weights[$i] * $min;
                $weightedD2[$i] = $value;
                $sum += $value;
            }

            if ($sum <= 0.0) {
                // All remaining points coincide with chosen centroids; deterministically
                // fill the rest with the lowest-index unused points.
                $next = $this->firstUnusedIndex($n, $chosen);
                if ($next === null) {
                    break;
                }
                $chosen[] = $next;
                continue;
            }

            $target = $this->nextUnitFloat($randomizer) * $sum;
            $chosen[] = $this->sampleByCumulativeFloat($weightedD2, $target);
        }

        $centroids = [];
        foreach ($chosen as $index) {
            $centroids[] = $points[$index];
        }

        return $centroids;
    }

    /**
     * @param list<array{0:float,1:float,2:float}> $points
     * @param list<int>                            $weights
     * @param list<int>                            $assignments
     * @param list<array{0:float,1:float,2:float}> $previous  fallback for empty clusters
     *
     * @return list<array{0:float,1:float,2:float}>
     */
    private function recomputeCentroids(array $points, array $weights, array $assignments, array $previous): array
    {
        $k = count($previous);
        $sumL = array_fill(0, $k, 0.0);
        $sumA = array_fill(0, $k, 0.0);
        $sumB = array_fill(0, $k, 0.0);
        $sumW = array_fill(0, $k, 0);

        foreach ($points as $i => $point) {
            $c = $assignments[$i];
            $w = $weights[$i];
            $sumL[$c] += $point[0] * $w;
            $sumA[$c] += $point[1] * $w;
            $sumB[$c] += $point[2] * $w;
            $sumW[$c] += $w;
        }

        $centroids = [];
        for ($c = 0; $c < $k; $c++) {
            if ($sumW[$c] === 0) {
                $centroids[] = $previous[$c];
                continue;
            }
            $centroids[] = [$sumL[$c] / $sumW[$c], $sumA[$c] / $sumW[$c], $sumB[$c] / $sumW[$c]];
        }

        return $centroids;
    }

    /**
     * A uniform float in [0, 1] from the seeded engine. Used instead of
     * Randomizer::nextFloat(), which only exists on PHP >= 8.3 (our floor is 8.2).
     */
    private function nextUnitFloat(Randomizer $randomizer): float
    {
        return $randomizer->getInt(0, PHP_INT_MAX) / PHP_INT_MAX;
    }

    /**
     * @param list<int> $weights
     */
    private function sampleByCumulativeInt(array $weights, int $target): int
    {
        $cumulative = 0;
        foreach ($weights as $i => $weight) {
            $cumulative += $weight;
            if ($target < $cumulative) {
                return $i;
            }
        }

        return count($weights) - 1;
    }

    /**
     * @param array<int, float> $weights
     */
    private function sampleByCumulativeFloat(array $weights, float $target): int
    {
        $cumulative = 0.0;
        $last = 0;
        foreach ($weights as $i => $weight) {
            $cumulative += $weight;
            $last = $i;
            if ($target < $cumulative) {
                return $i;
            }
        }

        return $last;
    }

    /**
     * @param list<int> $chosen
     */
    private function firstUnusedIndex(int $n, array $chosen): ?int
    {
        $used = array_flip($chosen);
        for ($i = 0; $i < $n; $i++) {
            if (!isset($used[$i])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param array{0:float,1:float,2:float} $a
     * @param array{0:float,1:float,2:float} $b
     */
    private function distanceSq(array $a, array $b): float
    {
        return ($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2;
    }
}
