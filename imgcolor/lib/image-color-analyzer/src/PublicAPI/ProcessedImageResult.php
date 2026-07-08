<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\PublicAPI;

use ImageColorAnalyzer\Contracts\BoundingBox;
use ImageColorAnalyzer\Contracts\EncodedImage;

final readonly class ProcessedImageResult
{
    public function __construct(
        public string $json,
        public EncodedImage $croppedImage,
        public BoundingBox $sourceBoundingBox,
        public bool $wasCropped,
    ) {
    }
}
