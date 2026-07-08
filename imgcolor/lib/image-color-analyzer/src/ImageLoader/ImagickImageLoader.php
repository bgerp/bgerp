<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ImageLoader;

use ImageColorAnalyzer\Contracts\ImageFormat;
use ImageColorAnalyzer\Contracts\ImageLoaderInterface;
use ImageColorAnalyzer\Contracts\ImageSource;
use ImageColorAnalyzer\Contracts\Raster;
use ImageColorAnalyzer\Exception\InvalidImageException;
use ImageColorAnalyzer\Exception\UnsupportedImageException;
use Throwable;

/**
 * Optional Imagick-backed adapter. Imagick normalizes tricky JPEGs, then the
 * resulting PNG bytes are decoded through the same GD-to-Raster path.
 */
final class ImagickImageLoader implements ImageLoaderInterface
{
    use ReadsImageSourceStreams;

    private const IMAGICK_CLASS = 'Imagick';

    private readonly GdImageLoader $gdLoader;

    public function __construct(?GdImageLoader $gdLoader = null)
    {
        $this->gdLoader = $gdLoader ?? new GdImageLoader();
    }

    public function supports(ImageSource $source): bool
    {
        return class_exists(self::IMAGICK_CLASS)
            && in_array($source->detectedFormat(), [ImageFormat::PNG, ImageFormat::JPEG], true);
    }

    public function load(ImageSource $source): Raster
    {
        if (!$this->supports($source)) {
            throw new UnsupportedImageException('The Imagick extension is required for ImagickImageLoader.');
        }

        $imagick = $this->newImagick();
        $pngBytes = null;
        try {
            $this->invoke($imagick, 'readImageBlob', $this->readAllBytes($source));
            if (method_exists($imagick, 'setIteratorIndex')) {
                $this->invoke($imagick, 'setIteratorIndex', 0);
            }
            if (defined('Imagick::COLORSPACE_SRGB') && method_exists($imagick, 'setImageColorspace')) {
                $this->invoke($imagick, 'setImageColorspace', constant('Imagick::COLORSPACE_SRGB'));
            }
            $this->invoke($imagick, 'setImageFormat', 'png32');
            $pngBytes = $this->invoke($imagick, 'getImagesBlob');
        } catch (UnsupportedImageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InvalidImageException('Imagick could not decode the image bytes.', previous: $e);
        } finally {
            $this->release($imagick);
        }

        if (!is_string($pngBytes) || $pngBytes === '') {
            throw new InvalidImageException('Imagick did not return decoded PNG bytes.');
        }

        return $this->gdLoader->load(FileImageSource::fromBytes($pngBytes));
    }

    private function newImagick(): object
    {
        $class = self::IMAGICK_CLASS;
        if (!class_exists($class)) {
            throw new UnsupportedImageException('The Imagick extension is not loaded.');
        }

        return new $class();
    }

    private function invoke(object $target, string $method, mixed ...$arguments): mixed
    {
        if (!method_exists($target, $method)) {
            throw new UnsupportedImageException("Imagick method {$method} is unavailable.");
        }

        return $target->{$method}(...$arguments);
    }

    private function release(object $imagick): void
    {
        foreach (['clear', 'destroy'] as $method) {
            if (!method_exists($imagick, $method)) {
                continue;
            }

            try {
                $this->invoke($imagick, $method);
            } catch (Throwable) {
            }
        }
    }
}
