<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ImageLoader;

use ImageColorAnalyzer\Contracts\ImageSource;
use ImageColorAnalyzer\Exception\InvalidImageException;

trait ReadsImageSourceStreams
{
    private function readAllBytes(ImageSource $source): string
    {
        $stream = $source->stream();
        $bytes = stream_get_contents($stream);
        if ($bytes === false || $bytes === '') {
            throw new InvalidImageException('Image source is empty or unreadable.');
        }

        return $bytes;
    }
}
