<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

use InvalidArgumentException;

/**
 * Axis-aligned rectangle: origin (x, y) plus width and height, in pixels.
 */
final readonly class BoundingBox
{
    public function __construct(
        public int $x,
        public int $y,
        public int $width,
        public int $height,
    ) {
        if ($x < 0 || $y < 0 || $width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('BoundingBox requires non-negative origin and positive dimensions.');
        }
    }

    public function area(): int
    {
        return $this->width * $this->height;
    }
}
