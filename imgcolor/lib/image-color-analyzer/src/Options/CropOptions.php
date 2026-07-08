<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Options;

use InvalidArgumentException;

/**
 * Configuration for near-white background detection and cropping.
 */
final readonly class CropOptions
{
    /**
     * @param float $lightnessMin        minimum CIELAB L* for a pixel to count as "white"
     * @param float $chromaMax           maximum CIELAB chroma sqrt(a*^2 + b*^2) for "white"
     * @param float $lineContentFraction fraction of a scan line used to identify the main content block
     * @param int   $alphaThreshold      pixels with alpha below this are treated as background
     */
    public function __construct(
        public float $lightnessMin = 95.0,
        public float $chromaMax = 5.0,
        public float $lineContentFraction = 0.002,
        public int $alphaThreshold = 8,
    ) {
        if ($lightnessMin < 0.0 || $lightnessMin > 100.0) {
            throw new InvalidArgumentException('Crop lightnessMin must be in the range 0..100.');
        }
        if ($chromaMax < 0.0) {
            throw new InvalidArgumentException('Crop chromaMax must be non-negative.');
        }
        if ($lineContentFraction < 0.0 || $lineContentFraction > 1.0) {
            throw new InvalidArgumentException('Crop lineContentFraction must be in the range 0..1.');
        }
        if ($alphaThreshold < 0 || $alphaThreshold > 255) {
            throw new InvalidArgumentException('Crop alphaThreshold must be in the range 0..255.');
        }
    }
}
