<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

use ImageColorAnalyzer\Exception\ImageSaveException;
use InvalidArgumentException;
use Throwable;

final readonly class EncodedImage
{
    public ImageFormat $format;

    public string $mediaType;

    public function __construct(
        public string $bytes,
        public int $width,
        public int $height,
    ) {
        if ($bytes === '') {
            throw new InvalidArgumentException('Encoded image bytes must not be empty.');
        }
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Encoded image dimensions must be positive.');
        }

        $this->format = ImageFormat::PNG;
        $this->mediaType = 'image/png';
    }

    public function saveTo(string $path, bool $overwrite = false): void
    {
        if ($path === '') {
            throw new ImageSaveException('Cropped image destination must not be empty.');
        }

        set_error_handler(static fn (): bool => true);
        try {
            $this->writeToPath($path, $overwrite);
        } finally {
            restore_error_handler();
        }
    }

    private function writeToPath(string $path, bool $overwrite): void
    {
        if (!$overwrite) {
            $this->writeNewFile($path);

            return;
        }

        $this->replaceAtomically($path);
    }

    private function writeNewFile(string $path): void
    {
        $stream = $this->openDestination($path);

        try {
            $this->writeAll($stream, $path);
            $this->finalize($stream, $path);
        } catch (Throwable $exception) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($path)) {
                unlink($path);
            }

            $this->throwSaveFailure($path, $exception);
        }
    }

    private function replaceAtomically(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            throw new ImageSaveException("Unable to open cropped image destination: {$path}.");
        }

        $temporaryPath = tempnam($directory, '.ica-');
        if ($temporaryPath === false) {
            throw new ImageSaveException("Unable to open cropped image destination: {$path}.");
        }

        $stream = fopen($temporaryPath, 'wb');
        if ($stream === false) {
            unlink($temporaryPath);
            throw new ImageSaveException("Unable to open cropped image destination: {$path}.");
        }

        try {
            $this->writeAll($stream, $path);
            $this->finalize($stream, $path);

            if (!rename($temporaryPath, $path)) {
                throw new ImageSaveException("Unable to replace cropped image destination: {$path}.");
            }
        } catch (Throwable $exception) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }

            $this->throwSaveFailure($path, $exception);
        }
    }

    /**
     * @param resource $stream
     */
    private function finalize($stream, string $path): void
    {
        if (!fflush($stream) || !fclose($stream)) {
            throw new ImageSaveException("Unable to finalize cropped image destination: {$path}.");
        }
    }

    /**
     * @return resource
     */
    private function openDestination(string $path)
    {
        $stream = fopen($path, 'xb');

        if ($stream === false) {
            if (file_exists($path)) {
                throw new ImageSaveException("Cropped image destination already exists: {$path}.");
            }

            throw new ImageSaveException("Unable to open cropped image destination: {$path}.");
        }

        return $stream;
    }

    /**
     * @param resource $stream
     */
    private function writeAll($stream, string $path): void
    {
        $offset = 0;
        $length = strlen($this->bytes);

        while ($offset < $length) {
            $written = fwrite($stream, substr($this->bytes, $offset));
            if ($written === false || $written === 0) {
                throw new ImageSaveException("Unable to write complete cropped image to {$path}.");
            }
            $offset += $written;
        }
    }

    private function throwSaveFailure(string $path, Throwable $exception): never
    {
        if ($exception instanceof ImageSaveException) {
            throw $exception;
        }

        throw new ImageSaveException("Unable to save cropped image to {$path}.", previous: $exception);
    }
}
