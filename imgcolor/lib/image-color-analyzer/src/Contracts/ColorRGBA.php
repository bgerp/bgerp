<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

use InvalidArgumentException;

/**
 * Immutable 8-bit RGBA color. Transport format for input pixels and output colors.
 */
final readonly class ColorRGBA
{
    public function __construct(
        public int $r,
        public int $g,
        public int $b,
        public int $a = 255,
    ) {
        foreach (['r' => $r, 'g' => $g, 'b' => $b, 'a' => $a] as $name => $value) {
            if ($value < 0 || $value > 255) {
                throw new InvalidArgumentException("Channel {$name} out of range 0-255: {$value}");
            }
        }
    }

    public function isTransparent(int $alphaThreshold = 8): bool
    {
        return $this->a < $alphaThreshold;
    }

    public function toHex(): string
    {
        return sprintf('#%02X%02X%02X', $this->r, $this->g, $this->b);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    public function toRgbTriplet(): array
    {
        return [$this->r, $this->g, $this->b];
    }
}
