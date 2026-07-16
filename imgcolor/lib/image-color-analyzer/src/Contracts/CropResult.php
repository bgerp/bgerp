<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

final readonly class CropResult
{
    public function __construct(
        public Raster $raster,
        public BoundingBox $boundingBox,
        public bool $wasCropped,
    ) {
    }
}
