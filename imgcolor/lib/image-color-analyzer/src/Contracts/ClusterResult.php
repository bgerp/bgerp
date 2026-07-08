<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

final readonly class ClusterResult
{
    /**
     * @param list<Cluster> $clusters
     * @param int $totalAnalyzedPixels non-transparent pixels considered (denominator for coverage)
     */
    public function __construct(
        public array $clusters,
        public int $totalAnalyzedPixels,
    ) {
    }
}
