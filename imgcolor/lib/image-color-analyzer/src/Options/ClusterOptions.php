<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Options;

/**
 * Configuration for color histogram binning and k-means clustering.
 */
final readonly class ClusterOptions
{
    /**
     * @param int|null $fixedK                  fixed cluster count; null selects k automatically
     * @param int      $kMax                    upper bound for automatic k search
     * @param int      $histogramBitsPerChannel color quantization resolution (bits per RGB channel)
     * @param float    $mergeDeltaE             clusters closer than this (CIE76) are merged
     * @param float    $minClusterCoverage      clusters below this coverage fraction are merged away
     * @param int      $seed                    RNG seed for deterministic k-means++
     * @param int      $alphaThreshold          pixels with alpha below this are ignored
     */
    public function __construct(
        public ?int $fixedK = null,
        public int $kMax = 8,
        public int $histogramBitsPerChannel = 5,
        public float $mergeDeltaE = 3.0,
        public float $minClusterCoverage = 0.01,
        public int $seed = 1,
        public int $alphaThreshold = 8,
    ) {
    }
}
