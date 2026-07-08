<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ColorClusterer;

use ImageColorAnalyzer\Color\ColorConverter;

/**
 * OWNER: Developer C.
 *
 * Chooses the number of clusters k using two views of weighted silhouette over
 * the histogram bins for k in 2..kMax. A conservative bin-structure score keeps
 * gradients from fragmenting into one cluster per bin; a represented-pixel score
 * ranks the eligible candidates so meaningful one-bin accents keep their weight.
 * Within-cluster sum of squares (the elbow/WCSS curve) is available via
 * {@see WeightedKMeans::wcss()} for diagnostics.
 *
 * Silhouette is O(bins^2) per candidate k, so the scored set is capped to the
 * heaviest {@see SILHOUETTE_MAX_POINTS} bins (the rest contribute negligible
 * coverage); the final clustering in {@see KMeansClusterer} still uses every bin.
 */
final class KSelector
{
    /** Upper bound on bins fed to the O(n^2) silhouette to keep k-selection cheap. */
    public const SILHOUETTE_MAX_POINTS = 256;

    /**
     * Minimum silhouette score to accept a sub-grouping as "real structure".
     * The conventional reading of silhouette values: > 0.7 strong, 0.5–0.7
     * reasonable, < 0.5 weak. Below this, the bins are treated as mutually
     * distinct colors (k = bin count, capped by kMax) and trimmed by the merge pass.
     */
    public const STRUCTURE_THRESHOLD = 0.5;

    private readonly WeightedKMeans $kmeans;

    public function __construct(
        private readonly ColorConverter $converter,
        ?WeightedKMeans $kmeans = null,
    ) {
        $this->kmeans = $kmeans ?? new WeightedKMeans();
    }

    /**
     * @param list<array{0:float,1:float,2:float}> $labPoints one Lab triplet per histogram bin
     * @param list<int>                            $weights   pixel count per bin (same index)
     * @param int                                  $kMax      inclusive upper bound for the search
     * @param int                                  $seed      RNG seed forwarded to k-means++
     *
     * @return int chosen cluster count, in 1..min($kMax, count($labPoints))
     */
    public function select(array $labPoints, array $weights, int $kMax, int $seed = 1): int
    {
        $n = count($labPoints);
        if ($n <= 2) {
            return max(1, $n);
        }

        [$points, $pointWeights] = $this->capByWeight($labPoints, $weights, self::SILHOUETTE_MAX_POINTS);
        $m = count($points);

        // Silhouette needs at least one multi-point cluster, so it can only score
        // k up to m - 1; the all-singleton clustering (k = m) is handled below.
        $searchUpper = min($kMax, $m - 1);

        $bestK = 0;
        $bestPixelScore = -INF;
        for ($k = 2; $k <= $searchUpper; $k++) {
            $result = $this->kmeans->run($points, $pointWeights, $k, $seed);
            [$structureScore, $pixelScore] = $this->weightedSilhouettes(
                $points,
                $pointWeights,
                $result['assignments'],
                $k,
            );

            // Require structure across multiple histogram bins before a candidate
            // can win. Among structurally valid candidates, account for the full
            // represented pixel population so a substantial one-bin accent is not
            // discarded merely because histogram compression made it a singleton.
            if ($structureScore >= self::STRUCTURE_THRESHOLD && $pixelScore > $bestPixelScore) {
                $bestPixelScore = $pixelScore;
                $bestK = $k;
            }
        }

        // A clearly good grouping wins outright. Otherwise the bins have no strong
        // sub-structure (e.g. a handful of mutually distinct print colors), so treat
        // each heavy bin as its own color and let the merge pass fold the rest.
        if ($bestK > 0) {
            return $bestK;
        }

        return max(1, min($kMax, $m));
    }

    /**
     * Keeps the $limit heaviest bins (ties broken by lowest index), preserving
     * their original relative order so k-means++ sampling stays deterministic.
     *
     * @param list<array{0:float,1:float,2:float}> $labPoints
     * @param list<int>                            $weights
     *
     * @return array{0: list<array{0:float,1:float,2:float}>, 1: list<int>}
     */
    private function capByWeight(array $labPoints, array $weights, int $limit): array
    {
        $n = count($labPoints);
        if ($n <= $limit) {
            return [$labPoints, $weights];
        }

        $indices = range(0, $n - 1);
        usort($indices, static function (int $a, int $b) use ($weights): int {
            return $weights[$b] <=> $weights[$a] ?: $a <=> $b;
        });
        $kept = array_slice($indices, 0, $limit);
        sort($kept);

        $points = [];
        $pointWeights = [];
        foreach ($kept as $index) {
            $points[] = $labPoints[$index];
            $pointWeights[] = $weights[$index];
        }

        return [$points, $pointWeights];
    }

    /**
     * Returns two weighted mean silhouettes in [-1, 1]. The structure score
     * applies the standard singleton-bin convention and prevents histogram bins
     * from being over-selected as individual colors. The pixel score treats each
     * bin weight as coincident represented pixels, so a one-bin cluster with real
     * coverage can still influence the ranking of structurally valid candidates.
     *
     * @param list<array{0:float,1:float,2:float}> $points
     * @param list<int>                            $weights
     * @param list<int>                            $assignments
     *
     * @return array{0:float,1:float} structure score, represented-pixel score
     */
    private function weightedSilhouettes(array $points, array $weights, array $assignments, int $k): array
    {
        $n = count($points);

        // Track both represented pixel weight and histogram-bin count because the
        // two scores intentionally use different singleton conventions.
        $weightInCluster = array_fill(0, $k, 0);
        $countInCluster = array_fill(0, $k, 0);
        foreach ($assignments as $i => $cluster) {
            $weightInCluster[$cluster] += $weights[$i];
            $countInCluster[$cluster]++;
        }

        $totalWeight = 0;
        $weightedStructureScore = 0.0;
        $weightedPixelScore = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $own = $assignments[$i];
            $totalWeight += $weights[$i];

            /** @var list<float> $distanceToCluster */
            $distanceToCluster = array_fill(0, $k, 0.0);
            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) {
                    continue;
                }
                $distanceToCluster[$assignments[$j]] += $weights[$j] * $this->converter->deltaE($points[$i], $points[$j]);
            }

            $b = INF;
            for ($c = 0; $c < $k; $c++) {
                if ($c === $own || $weightInCluster[$c] === 0) {
                    continue;
                }
                $mean = $distanceToCluster[$c] / $weightInCluster[$c];
                if ($mean < $b) {
                    $b = $mean;
                }
            }

            if ($b === INF) {
                continue;
            }

            if ($countInCluster[$own] > 1) {
                $otherBinWeight = $weightInCluster[$own] - $weights[$i];
                $a = $otherBinWeight > 0 ? $distanceToCluster[$own] / $otherBinWeight : 0.0;
                $max = max($a, $b);
                $structureScore = $max > 0.0 ? ($b - $a) / $max : 0.0;
                $weightedStructureScore += $weights[$i] * $structureScore;
            }

            if ($weightInCluster[$own] > 1) {
                $a = $distanceToCluster[$own] / ($weightInCluster[$own] - 1);
                $max = max($a, $b);
                $pixelScore = $max > 0.0 ? ($b - $a) / $max : 0.0;
                $weightedPixelScore += $weights[$i] * $pixelScore;
            }
        }

        if ($totalWeight === 0) {
            return [0.0, 0.0];
        }

        return [$weightedStructureScore / $totalWeight, $weightedPixelScore / $totalWeight];
    }
}
