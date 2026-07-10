<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

final readonly class Cluster
{
    /**
     * @param array{0:float,1:float,2:float} $lab centroid in CIELAB
     * @param int $weight number of (non-transparent) pixels assigned to this cluster
     */
    public function __construct(
        public ColorRGBA $centroid,
        public array $lab,
        public int $weight,
    ) {
    }
}
