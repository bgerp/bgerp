<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Exception;

use RuntimeException;

final class InvalidImageException extends RuntimeException implements ImageAnalyzerException
{
}
