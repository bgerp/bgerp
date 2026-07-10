<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ImageLoader;

use ImageColorAnalyzer\Contracts\ImageSource;
use ImageColorAnalyzer\Exception\InvalidImageException;
use InvalidArgumentException;
use Throwable;

/**
 * Normalizes public input forms into a seekable, format-sniffed ImageSource.
 */
final class SourceResolver
{
    public function resolve(mixed $source): ImageSource
    {
        if ($source instanceof ImageSource) {
            return $source;
        }

        if ($source instanceof \GdImage) {
            return FileImageSource::fromBytes($this->encodeGdImage($source));
        }

        if (is_resource($source)) {
            if (get_resource_type($source) === 'gd') {
                return FileImageSource::fromBytes($this->encodeLegacyGdResource($source));
            }

            return FileImageSource::fromStream($source);
        }

        if (is_string($source)) {
            return FileImageSource::fromBytes($source);
        }

        throw new InvalidArgumentException(
            'Source must be an ImageSource, a stream resource, raw image bytes, or a GD image.',
        );
    }

    public function resolvePath(string $path): ImageSource
    {
        return FileImageSource::fromPath($path);
    }

    private function encodeGdImage(\GdImage $image): string
    {
        return $this->encodePng($image);
    }

    /**
     * @param resource $image
     */
    private function encodeLegacyGdResource($image): string
    {
        return $this->encodePng($image);
    }

    /**
     * @param \GdImage|resource $image
     */
    private function encodePng($image): string
    {
        $bufferLevel = ob_get_level();
        ob_start();
        set_error_handler(static fn (): bool => true);
        try {
            /** @phpstan-ignore-next-line PHP 8.3 uses GdImage objects; this supports legacy callers. */
            $encoded = imagepng($image);
            $bytes = ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw new InvalidImageException('Unable to encode GD image source.', previous: $e);
        } finally {
            restore_error_handler();
        }

        if ($encoded === false || !is_string($bytes)) {
            throw new InvalidImageException('Unable to encode GD image source.');
        }

        return $bytes;
    }
}
