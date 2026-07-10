<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Exception;

use RuntimeException;

final class ImageEncodingException extends RuntimeException implements ImageAnalyzerException
{
}
