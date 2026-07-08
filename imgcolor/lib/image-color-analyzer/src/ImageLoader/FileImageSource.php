<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ImageLoader;

use ImageColorAnalyzer\Contracts\ImageFormat;
use ImageColorAnalyzer\Contracts\ImageSource;
use ImageColorAnalyzer\Exception\InvalidImageException;
use ImageColorAnalyzer\Exception\UnsupportedImageException;

/**
 * OWNER: Developer A (foundation).
 *
 * Wraps a file path or stream resource, sniffing PNG/JPEG from magic bytes
 * (never from the file extension).
 */
final class FileImageSource implements ImageSource
{
    /**
     * @param resource $stream
     */
    private function __construct(
        private $stream,
        private readonly ImageFormat $format,
    ) {
    }

    public static function fromPath(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidImageException("Cannot open image file: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidImageException("Cannot open image file: {$path}");
        }

        return self::fromStream($handle);
    }

    public static function fromBytes(string $bytes): self
    {
        $stream = self::openTemporaryStream();
        $written = fwrite($stream, $bytes);
        if ($written === false || $written !== strlen($bytes)) {
            throw new InvalidImageException('Unable to write image bytes to an in-memory stream.');
        }
        rewind($stream);

        return new self($stream, self::sniff($stream));
    }

    /**
     * @param resource $handle
     */
    public static function fromStream($handle): self
    {
        if (!is_resource($handle) || get_resource_type($handle) !== 'stream') {
            throw new InvalidImageException('Expected a valid, readable stream resource.');
        }

        if (!self::isSeekable($handle)) {
            $bytes = stream_get_contents($handle);
            if ($bytes === false) {
                throw new InvalidImageException('Unable to read image bytes from stream.');
            }

            return self::fromBytes($bytes);
        }

        return new self($handle, self::sniff($handle));
    }

    /**
     * @return resource
     */
    public function stream()
    {
        rewind($this->stream);

        return $this->stream;
    }

    public function detectedFormat(): ImageFormat
    {
        return $this->format;
    }

    /**
     * @param resource $handle
     */
    private static function sniff($handle): ImageFormat
    {
        rewind($handle);
        $magic = (string) fread($handle, 8);
        rewind($handle);

        if (str_starts_with($magic, "\x89PNG\x0d\x0a\x1a\x0a")) {
            return ImageFormat::PNG;
        }
        if (str_starts_with($magic, "\xFF\xD8\xFF")) {
            return ImageFormat::JPEG;
        }

        throw new UnsupportedImageException('Only PNG and JPEG sources are supported.');
    }

    /**
     * @param resource $handle
     */
    private static function isSeekable($handle): bool
    {
        $metadata = stream_get_meta_data($handle);
        if ($metadata['seekable'] !== true) {
            return false;
        }

        set_error_handler(static fn (): bool => true);
        try {
            return fseek($handle, 0, SEEK_CUR) === 0;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return resource
     */
    private static function openTemporaryStream()
    {
        $stream = fopen('php://temp', 'r+b');
        if (!is_resource($stream)) {
            throw new InvalidImageException('Unable to open an in-memory stream.');
        }

        return $stream;
    }
}
