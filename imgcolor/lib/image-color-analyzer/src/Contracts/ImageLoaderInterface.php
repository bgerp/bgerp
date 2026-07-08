<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

interface ImageLoaderInterface
{
    public function supports(ImageSource $source): bool;

    public function load(ImageSource $source): Raster;
}
