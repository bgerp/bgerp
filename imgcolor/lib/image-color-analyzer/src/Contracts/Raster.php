<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

/**
 * An immutable, decoded bitmap. The shared "currency" passed between components.
 * The default loader returns {@see \ImageColorAnalyzer\ImageLoader\GdRaster};
 * {@see \ImageColorAnalyzer\ImageLoader\InMemoryRaster} remains available for
 * synthetic and custom rasters.
 */
interface Raster
{
    public function width(): int;

    public function height(): int;

    public function hasAlpha(): bool;

    public function pixelAt(int $x, int $y): ColorRGBA;

    /**
     * Row-major iteration over every pixel (top-left to bottom-right).
     *
     * @return iterable<ColorRGBA>
     */
    public function pixels(): iterable;

    public function crop(BoundingBox $box): Raster;
}
