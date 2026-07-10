<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

/**
 * One principal color and its share of the analyzed area.
 */
final readonly class ColorCoverage
{
    /**
     * @param string $color hex string "#RRGGBB"
     * @param array{0:int,1:int,2:int} $rgb
     */
    public function __construct(
        public string $color,
        public array $rgb,
        public float $coveragePercent,
    ) {
    }

    /**
     * @return array{color:string,coverage_percent:float}
     */
    public function toArray(): array
    {
        return [
            'color' => $this->color,
            'coverage_percent' => $this->coveragePercent,
        ];
    }
}
