<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ImageLoader;

use ImageColorAnalyzer\Contracts\BoundingBox;
use ImageColorAnalyzer\Contracts\ColorRGBA;
use ImageColorAnalyzer\Contracts\Raster;
use ImageColorAnalyzer\Exception\ImageEncodingException;
use ImageColorAnalyzer\Exception\InvalidImageException;
use InvalidArgumentException;

/**
 * Immutable raster view over a normalized truecolor GD image.
 *
 * Pixels are decoded on demand, so memory usage follows GD's native bitmap
 * storage instead of retaining one PHP object per pixel. Crops are lightweight
 * coordinate views over the same private image handle.
 *
 * @internal Created by {@see GdImageLoader}; downstream code should depend on Raster.
 */
final class GdRaster implements Raster
{
    private readonly int $width;

    private readonly int $height;

    private ?bool $hasAlpha = null;

    public function __construct(
        private readonly \GdImage $image,
        private readonly int $originX = 0,
        private readonly int $originY = 0,
        ?int $width = null,
        ?int $height = null,
    ) {
        if (!imageistruecolor($image)) {
            throw new InvalidArgumentException('GdRaster requires a normalized truecolor image.');
        }

        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $width ??= $imageWidth - $originX;
        $height ??= $imageHeight - $originY;

        if (
            $originX < 0
            || $originY < 0
            || $width <= 0
            || $height <= 0
            || $width > $imageWidth
            || $height > $imageHeight
            || $originX > $imageWidth - $width
            || $originY > $imageHeight - $height
        ) {
            throw new InvalidArgumentException('Raster view exceeds the GD image bounds.');
        }

        $this->width = $width;
        $this->height = $height;
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
        if ($this->hasAlpha !== null) {
            return $this->hasAlpha;
        }

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (($this->readRawPixel($this->originX + $x, $this->originY + $y) & 0x7F000000) !== 0) {
                    return $this->hasAlpha = true;
                }
            }
        }

        return $this->hasAlpha = false;
    }

    public function pixelAt(int $x, int $y): ColorRGBA
    {
        if ($x < 0 || $y < 0 || $x >= $this->width || $y >= $this->height) {
            throw new InvalidArgumentException("Pixel ({$x},{$y}) is out of bounds.");
        }

        return $this->colorAt($this->originX + $x, $this->originY + $y);
    }

    public function pixels(): iterable
    {
        $maxX = $this->originX + $this->width;
        $maxY = $this->originY + $this->height;

        for ($y = $this->originY; $y < $maxY; $y++) {
            for ($x = $this->originX; $x < $maxX; $x++) {
                yield $this->colorAt($x, $y);
            }
        }
    }

    public function crop(BoundingBox $box): Raster
    {
        if ($box->width > $this->width || $box->height > $this->height) {
            throw new InvalidArgumentException('Crop box exceeds raster bounds.');
        }
        if ($box->x > $this->width - $box->width || $box->y > $this->height - $box->height) {
            throw new InvalidArgumentException('Crop box exceeds raster bounds.');
        }

        return new self(
            $this->image,
            $this->originX + $box->x,
            $this->originY + $box->y,
            $box->width,
            $box->height,
        );
    }

    /**
     * Copies this raster view with native GD operations for the PNG encoder.
     *
     * @internal
     */
    public function copyToGdImage(): \GdImage
    {
        $copy = imagecrop($this->image, [
            'x' => $this->originX,
            'y' => $this->originY,
            'width' => $this->width,
            'height' => $this->height,
        ]);
        if (!$copy instanceof \GdImage) {
            throw new ImageEncodingException('Unable to copy the GD raster view for PNG encoding.');
        }

        imagealphablending($copy, false);
        imagesavealpha($copy, true);

        return $copy;
    }

    private function colorAt(int $x, int $y): ColorRGBA
    {
        $rgba = $this->readRawPixel($x, $y);
        $gdAlpha = ($rgba & 0x7F000000) >> 24;

        return new ColorRGBA(
            ($rgba >> 16) & 0xFF,
            ($rgba >> 8) & 0xFF,
            $rgba & 0xFF,
            (int) round((127 - $gdAlpha) * 255 / 127),
        );
    }

    private function readRawPixel(int $x, int $y): int
    {
        $rgba = imagecolorat($this->image, $x, $y);
        if ($rgba === false) {
            throw new InvalidImageException("Unable to read pixel ({$x},{$y}) from GD image.");
        }

        return $rgba;
    }
}
