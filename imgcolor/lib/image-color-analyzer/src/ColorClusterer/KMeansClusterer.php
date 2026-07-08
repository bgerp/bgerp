<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ColorClusterer;

use ImageColorAnalyzer\Color\ColorConverter;
use ImageColorAnalyzer\Contracts\Cluster;
use ImageColorAnalyzer\Contracts\ClustererInterface;
use ImageColorAnalyzer\Contracts\ClusterResult;
use ImageColorAnalyzer\Contracts\ColorRGBA;
use ImageColorAnalyzer\Contracts\Raster;
use ImageColorAnalyzer\Options\ClusterOptions;

/**
 * OWNER: Developer C.
 *
 * k-means (Lloyd) with k-means++ seeded initialization, run in CIELAB over the
 * weighted histogram produced by {@see ColorHistogram}. k is fixed
 * (`options->fixedK`) or chosen by {@see KSelector}. A post-pass merges clusters
 * that are within `mergeDeltaE` of each other or below `minClusterCoverage`, so
 * anti-aliasing halos and JPEG fringes do not surface as principal colors.
 *
 * Output colors are the weight-weighted average of member bins' representative
 * RGB (always in-gamut), which avoids needing a Lab->RGB inverse. Determinism is
 * guaranteed for a fixed `options->seed` (see {@see WeightedKMeans}).
 *
 * A working cluster is accumulated as summed weighted channels so that merges are
 * a cheap addition of sums:
 *   array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}
 */
final class KMeansClusterer implements ClustererInterface
{
    public function __construct(
        private readonly ColorConverter $converter,
        private readonly ColorHistogram $histogram,
        private readonly KSelector $kSelector,
        private readonly WeightedKMeans $kmeans = new WeightedKMeans(),
    ) {
    }

    public function cluster(Raster $image, ClusterOptions $options): ClusterResult
    {
        $histogram = $this->histogram->build($image, $options->histogramBitsPerChannel, $options->alphaThreshold);
        $total = $histogram['total'];

        if ($total === 0) {
            return new ClusterResult([], 0);
        }

        $rgbPoints = $histogram['colors'];
        $weights = $histogram['weights'];

        $labPoints = [];
        foreach ($rgbPoints as [$r, $g, $b]) {
            $labPoints[] = $this->converter->rgbToLab(new ColorRGBA($r, $g, $b));
        }

        $k = $this->resolveK($labPoints, $weights, $options);
        $result = $this->kmeans->run($labPoints, $weights, $k, $options->seed);

        $clusters = $this->accumulate($rgbPoints, $labPoints, $weights, $result['assignments'], count($result['centroids']));
        $clusters = $this->mergeByDeltaE($clusters, $options->mergeDeltaE);
        $clusters = $this->foldLowCoverage($clusters, $total, $options->minClusterCoverage);

        return new ClusterResult($this->finalize($clusters), $total);
    }

    /**
     * @param list<array{0:float,1:float,2:float}> $labPoints
     * @param list<int>                            $weights
     */
    private function resolveK(array $labPoints, array $weights, ClusterOptions $options): int
    {
        $unique = count($labPoints);

        if ($options->fixedK !== null) {
            return max(1, min($options->fixedK, $unique));
        }
        if ($unique <= 2) {
            return $unique;
        }

        return $this->kSelector->select($labPoints, $weights, $options->kMax, $options->seed);
    }

    /**
     * Groups bins by their assigned centroid into summed working clusters.
     *
     * @param list<array{0:int,1:int,2:int}>       $rgbPoints
     * @param list<array{0:float,1:float,2:float}> $labPoints
     * @param list<int>                            $weights
     * @param list<int>                            $assignments
     *
     * @return list<array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}>
     */
    private function accumulate(array $rgbPoints, array $labPoints, array $weights, array $assignments, int $k): array
    {
        /** @var array<int, array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}> $acc */
        $acc = [];
        foreach ($labPoints as $i => $lab) {
            $c = $assignments[$i];
            $w = $weights[$i];
            [$r, $g, $b] = $rgbPoints[$i];

            if (!isset($acc[$c])) {
                $acc[$c] = ['sumR' => 0.0, 'sumG' => 0.0, 'sumB' => 0.0, 'sumL' => 0.0, 'sumA' => 0.0, 'sumBLab' => 0.0, 'weight' => 0];
            }
            $acc[$c]['sumR'] += $r * $w;
            $acc[$c]['sumG'] += $g * $w;
            $acc[$c]['sumB'] += $b * $w;
            $acc[$c]['sumL'] += $lab[0] * $w;
            $acc[$c]['sumA'] += $lab[1] * $w;
            $acc[$c]['sumBLab'] += $lab[2] * $w;
            $acc[$c]['weight'] += $w;
        }

        // Reindex to a dense list in centroid order; empty clusters are dropped.
        $clusters = [];
        for ($c = 0; $c < $k; $c++) {
            if (isset($acc[$c]) && $acc[$c]['weight'] > 0) {
                $clusters[] = $acc[$c];
            }
        }

        return $clusters;
    }

    /**
     * Repeatedly merges the closest pair of clusters while their centroids are
     * within `$threshold` delta-E. Deterministic: always merges the lowest-index
     * pair among equally-close candidates.
     *
     * @param list<array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}> $clusters
     *
     * @return list<array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}>
     */
    private function mergeByDeltaE(array $clusters, float $threshold): array
    {
        while (count($clusters) > 1) {
            $closest = INF;
            $mergeA = -1;
            $mergeB = -1;
            $count = count($clusters);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $distance = $this->converter->deltaE($this->labOf($clusters[$i]), $this->labOf($clusters[$j]));
                    if ($distance < $closest) {
                        $closest = $distance;
                        $mergeA = $i;
                        $mergeB = $j;
                    }
                }
            }

            if ($closest >= $threshold || $mergeA < 0) {
                break;
            }

            $clusters[$mergeA] = $this->combine($clusters[$mergeA], $clusters[$mergeB]);
            array_splice($clusters, $mergeB, 1);
        }

        return $clusters;
    }

    /**
     * Folds clusters below `$minCoverage` (as a fraction of $total) into their
     * nearest surviving neighbor, smallest coverage first, until every remaining
     * cluster clears the floor or only one cluster is left.
     *
     * @param list<array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}> $clusters
     *
     * @return list<array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}>
     */
    private function foldLowCoverage(array $clusters, int $total, float $minCoverage): array
    {
        $floor = $minCoverage * $total;

        while (count($clusters) > 1) {
            $smallest = -1;
            $smallestWeight = INF;
            foreach ($clusters as $i => $cluster) {
                if ($cluster['weight'] < $smallestWeight) {
                    $smallestWeight = $cluster['weight'];
                    $smallest = $i;
                }
            }

            if ($smallest < 0 || $clusters[$smallest]['weight'] >= $floor) {
                break;
            }

            $target = $this->nearestOther($clusters, $smallest);
            $clusters[$target] = $this->combine($clusters[$target], $clusters[$smallest]);
            array_splice($clusters, $smallest, 1);
        }

        return $clusters;
    }

    /**
     * @param list<array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}> $clusters
     */
    private function nearestOther(array $clusters, int $from): int
    {
        $best = -1;
        $bestDistance = INF;
        foreach ($clusters as $i => $cluster) {
            if ($i === $from) {
                continue;
            }
            $distance = $this->converter->deltaE($this->labOf($clusters[$from]), $this->labOf($cluster));
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $i;
            }
        }

        return $best;
    }

    /**
     * @param array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int} $a
     * @param array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int} $b
     *
     * @return array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}
     */
    private function combine(array $a, array $b): array
    {
        return [
            'sumR' => $a['sumR'] + $b['sumR'],
            'sumG' => $a['sumG'] + $b['sumG'],
            'sumB' => $a['sumB'] + $b['sumB'],
            'sumL' => $a['sumL'] + $b['sumL'],
            'sumA' => $a['sumA'] + $b['sumA'],
            'sumBLab' => $a['sumBLab'] + $b['sumBLab'],
            'weight' => $a['weight'] + $b['weight'],
        ];
    }

    /**
     * @param array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int} $cluster
     *
     * @return array{0:float,1:float,2:float}
     */
    private function labOf(array $cluster): array
    {
        $w = $cluster['weight'];

        return [$cluster['sumL'] / $w, $cluster['sumA'] / $w, $cluster['sumBLab'] / $w];
    }

    /**
     * Converts working clusters to the immutable {@see Cluster} DTOs, sorted by
     * weight descending (ties broken by hex ascending) for stable output.
     *
     * @param list<array{sumR:float, sumG:float, sumB:float, sumL:float, sumA:float, sumBLab:float, weight:int}> $clusters
     *
     * @return list<Cluster>
     */
    private function finalize(array $clusters): array
    {
        $built = [];
        foreach ($clusters as $cluster) {
            $w = $cluster['weight'];
            $centroid = new ColorRGBA(
                $this->clamp((int) round($cluster['sumR'] / $w)),
                $this->clamp((int) round($cluster['sumG'] / $w)),
                $this->clamp((int) round($cluster['sumB'] / $w)),
            );
            $built[] = new Cluster($centroid, $this->labOf($cluster), $w);
        }

        usort($built, static function (Cluster $a, Cluster $b): int {
            return $b->weight <=> $a->weight ?: strcmp($a->centroid->toHex(), $b->centroid->toHex());
        });

        return $built;
    }

    private function clamp(int $channel): int
    {
        return max(0, min(255, $channel));
    }
}
