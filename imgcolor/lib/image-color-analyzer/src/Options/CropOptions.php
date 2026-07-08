<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Options;

/**
 * Configuration for near-white background detection and cropping.
 */
final readonly class CropOptions
{
    /**
     * @param float $lightnessMin        minimum CIELAB L* for a pixel to count as "white"
     * @param float $chromaMax           maximum CIELAB chroma sqrt(a*^2 + b*^2) for "white"
     * @param float $lineContentFraction fraction of a scan line that must be content before that edge stops (noise guard)
     * @param int   $alphaThreshold      pixels with alpha below this are treated as background
     */
    public function __construct(
        public float $lightnessMin = 95.0,
        public float $chromaMax = 5.0,
        public float $lineContentFraction = 0.002,
        public int $alphaThreshold = 8,
    ) {
    }
}
