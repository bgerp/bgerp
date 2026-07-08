<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

/**
 * A readable source of image bytes. Implementations may wrap a file path,
 * a PHP stream resource, or an in-memory buffer.
 */
interface ImageSource
{
    /**
     * A readable, rewindable stream positioned at the start of the image bytes.
     *
     * @return resource
     */
    public function stream();

    public function detectedFormat(): ImageFormat;
}
