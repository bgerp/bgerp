<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

use ImageColorAnalyzer\Options\CropOptions;

interface CropperInterface
{
    public function crop(Raster $image, CropOptions $options): CropResult;
}
