<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ImageLoader;

use ImageColorAnalyzer\Contracts\BoundingBox;
use ImageColorAnalyzer\Contracts\ColorRGBA;
use ImageColorAnalyzer\Contracts\Raster;
use InvalidArgumentException;

/**
 * OWNER: Developer A (foundation).
 *
 * Array-backed, immutable {@see Raster}. Deliberately simple so it can back both
 * real decoded images and synthetic test fixtures without depending on ext-gd.
 */
final class InMemoryRaster implements Raster
{
    /**
     * @param list<ColorRGBA> $pixels row-major; length must equal width * height
     */
    public function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly array $pixels,
        private readonly bool $hasAlpha = false,
    ) {
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Raster dimensions must be positive.');
        }
        if (count($pixels) !== $width * $height) {
            throw new InvalidArgumentException(sprintf(
                'Pixel count %d does not match dimensions %dx%d.',
                count($pixels),
                $width,
                $height,
            ));
        }
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function hasAlpha(): bool
    {
        return $this->hasAlpha;
    }

    public function pixelAt(int $x, int $y): ColorRGBA
    {
        if ($x < 0 || $y < 0 || $x >= $this->width || $y >= $this->height) {
            throw new InvalidArgumentException("Pixel ({$x},{$y}) is out of bounds.");
        }

        return $this->pixels[$y * $this->width + $x];
    }

    public function pixels(): iterable
    {
        yield from $this->pixels;
    }

    public function crop(BoundingBox $box): Raster
    {
        if ($box->x + $box->width > $this->width || $box->y + $box->height > $this->height) {
            throw new InvalidArgumentException('Crop box exceeds raster bounds.');
        }

        $cropped = [];
        for ($row = 0; $row < $box->height; $row++) {
            $rowStart = ($box->y + $row) * $this->width + $box->x;
            for ($col = 0; $col < $box->width; $col++) {
                $cropped[] = $this->pixels[$rowStart + $col];
            }
        }

        return new self($box->width, $box->height, $cropped, $this->hasAlpha);
    }
}
