<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\ImageEncoder;

use ErrorException;
use ImageColorAnalyzer\Contracts\EncodedImage;
use ImageColorAnalyzer\Contracts\PngEncoderInterface;
use ImageColorAnalyzer\Contracts\Raster;
use ImageColorAnalyzer\Exception\ImageEncodingException;
use ImageColorAnalyzer\ImageLoader\GdRaster;
use Throwable;

final class GdPngEncoder implements PngEncoderInterface
{
    public function encode(Raster $image): EncodedImage
    {
        $stream = null;
        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        });

        try {
            $gdImage = $image instanceof GdRaster
                ? $image->copyToGdImage()
                : $this->rasterize($image);

            $stream = fopen('php://temp', 'w+b');
            if ($stream === false) {
                throw new ImageEncodingException('Unable to create a temporary PNG stream.');
            }
            if (!imagepng($gdImage, $stream)) {
                throw new ImageEncodingException('GD failed to encode the cropped image as PNG.');
            }
            if (!rewind($stream)) {
                throw new ImageEncodingException('Unable to rewind the encoded PNG stream.');
            }

            $bytes = stream_get_contents($stream);
            if ($bytes === false || $bytes === '') {
                throw new ImageEncodingException('GD returned empty PNG data.');
            }
        } catch (Throwable $exception) {
            throw new ImageEncodingException('Unable to encode cropped image as PNG.', previous: $exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            restore_error_handler();
        }

        return new EncodedImage($bytes, $image->width(), $image->height());
    }

    private function rasterize(Raster $raster): \GdImage
    {
        $image = imagecreatetruecolor($raster->width(), $raster->height());
        if (!$image instanceof \GdImage) {
            throw new ImageEncodingException('Unable to allocate a GD image for PNG encoding.');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        if ($transparent === false || !imagefilledrectangle(
            $image,
            0,
            0,
            $raster->width() - 1,
            $raster->height() - 1,
            $transparent,
        )) {
            throw new ImageEncodingException('Unable to initialize the PNG image buffer.');
        }

        $x = 0;
        $y = 0;
        foreach ($raster->pixels() as $pixel) {
            $gdAlpha = (int) round((255 - $pixel->a) * 127 / 255);
            $color = imagecolorallocatealpha($image, $pixel->r, $pixel->g, $pixel->b, $gdAlpha);
            if ($color === false || !imagesetpixel($image, $x, $y, $color)) {
                throw new ImageEncodingException("Unable to write PNG pixel ({$x},{$y}).");
            }

            if (++$x === $raster->width()) {
                $x = 0;
                ++$y;
            }
        }

        return $image;
    }
}
