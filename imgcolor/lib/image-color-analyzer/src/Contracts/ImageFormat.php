<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

enum ImageFormat: string
{
    case PNG = 'png';
    case JPEG = 'jpeg';
}
