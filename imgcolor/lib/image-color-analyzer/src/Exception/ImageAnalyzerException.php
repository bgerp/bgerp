<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Exception;

use Throwable;

/**
 * Marker interface implemented by every exception this library throws,
 * so consumers can catch the whole family with one catch block.
 */
interface ImageAnalyzerException extends Throwable
{
}
