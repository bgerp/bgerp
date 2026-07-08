<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ColorClusterer;

use ImageColorAnalyzer\Contracts\Raster;

/**
 * OWNER: Developer C.
 *
 * Reduces a raster to a weighted color histogram: bins colors to
 * `bitsPerChannel` resolution, skips transparent pixels, and returns unique
 * colors with their pixel counts plus the total analyzed pixel count. Running
 * clustering on this (instead of raw pixels) makes cost depend on color
 * diversity rather than image resolution — the single most important
 * performance lever in the library (see ADR-003).
 *
 * Each bin's representative color is the *weighted average* of the pixels that
 * fell into it (`round(sum / count)`), not the bin center. Averaging removes the
 * quantization bias that would otherwise pull centroids toward bin boundaries.
 */
final class ColorHistogram
{
    /**
     * Bins every non-transparent pixel of $image and returns the reduced set.
     *
     * @param int $bitsPerChannel color quantization resolution, 1..8 bits per RGB
     *                            channel (5 => 32 levels/channel => <= 32^3 bins)
     * @param int $alphaThreshold pixels with alpha below this are skipped entirely
     *
     * @return array{colors: list<array{0:int,1:int,2:int}>, weights: list<int>, total: int}
     *         `colors` are representative RGB triplets, `weights` the matching
     *         pixel counts (same index), `total` the number of analyzed
     *         (non-transparent) pixels. `total === array_sum($weights)`.
     */
    public function build(Raster $image, int $bitsPerChannel = 5, int $alphaThreshold = 8): array
    {
        $bits = max(1, min(8, $bitsPerChannel));
        $shift = 8 - $bits;

        /** @var array<int, array{r:int, g:int, b:int, count:int}> $bins keyed by packed quantized color */
        $bins = [];
        $total = 0;

        foreach ($image->pixels() as $pixel) {
            if ($pixel->isTransparent($alphaThreshold)) {
                continue;
            }

            $key = (($pixel->r >> $shift) << ($bits * 2))
                | (($pixel->g >> $shift) << $bits)
                | ($pixel->b >> $shift);

            if (isset($bins[$key])) {
                $bins[$key]['r'] += $pixel->r;
                $bins[$key]['g'] += $pixel->g;
                $bins[$key]['b'] += $pixel->b;
                $bins[$key]['count']++;
            } else {
                $bins[$key] = ['r' => $pixel->r, 'g' => $pixel->g, 'b' => $pixel->b, 'count' => 1];
            }

            $total++;
        }

        // Canonical order (by packed key) so downstream clustering is deterministic
        // regardless of pixel traversal order.
        ksort($bins);

        $colors = [];
        $weights = [];
        foreach ($bins as $bin) {
            $colors[] = [
                (int) round($bin['r'] / $bin['count']),
                (int) round($bin['g'] / $bin['count']),
                (int) round($bin['b'] / $bin['count']),
            ];
            $weights[] = $bin['count'];
        }

        return ['colors' => $colors, 'weights' => $weights, 'total' => $total];
    }
}
