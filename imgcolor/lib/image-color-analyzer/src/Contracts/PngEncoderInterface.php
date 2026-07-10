<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

interface PngEncoderInterface
{
    public function encode(Raster $image): EncodedImage;
}
