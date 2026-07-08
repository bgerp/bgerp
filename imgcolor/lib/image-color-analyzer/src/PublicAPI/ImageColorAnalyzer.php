<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\PublicAPI;

use ImageColorAnalyzer\Contracts\ClustererInterface;
use ImageColorAnalyzer\Contracts\CoverageCalculatorInterface;
use ImageColorAnalyzer\Contracts\CropperInterface;
use ImageColorAnalyzer\Contracts\CropResult;
use ImageColorAnalyzer\Contracts\ImageLoaderInterface;
use ImageColorAnalyzer\Contracts\PngEncoderInterface;
use ImageColorAnalyzer\ImageEncoder\GdPngEncoder;
use ImageColorAnalyzer\ImageLoader\SourceResolver;
use ImageColorAnalyzer\Options\AnalyzerOptions;

/**
 * Public entry point. Wires Loader -> Cropper -> Clusterer -> Coverage.
 * OWNER: skeleton by Developer A; final wiring is the joint integration task (T6).
 */
final class ImageColorAnalyzer
{
    private readonly SourceResolver $sourceResolver;

    private readonly PngEncoderInterface $pngEncoder;

    public function __construct(
        private readonly ImageLoaderInterface $loader,
        private readonly CropperInterface $cropper,
        private readonly ClustererInterface $clusterer,
        private readonly CoverageCalculatorInterface $coverage,
        ?SourceResolver $sourceResolver = null,
        ?PngEncoderInterface $pngEncoder = null,
    ) {
        $this->sourceResolver = $sourceResolver ?? new SourceResolver();
        $this->pngEncoder = $pngEncoder ?? new GdPngEncoder();
    }

    /**
     * @param mixed $source ImageSource, stream resource, raw image bytes, or GD image
     * @return list<array{color:string,coverage_percent:float}>
     */
    public function analyze(mixed $source, ?AnalyzerOptions $options = null): array
    {
        $options ??= new AnalyzerOptions();

        return $this->run($source, $options)['colors'];
    }

    /**
     * @return list<array{color:string,coverage_percent:float}>
     */
    public function analyzePath(string $path, ?AnalyzerOptions $options = null): array
    {
        return $this->analyze($this->sourceResolver->resolvePath($path), $options);
    }

    /**
     * @param mixed $source ImageSource, stream resource, raw image bytes, or GD image
     */
    public function analyzeAsJson(mixed $source, ?AnalyzerOptions $options = null): string
    {
        return $this->toJson($this->analyze($source, $options));
    }

    public function analyzePathAsJson(string $path, ?AnalyzerOptions $options = null): string
    {
        return $this->toJson($this->analyzePath($path, $options));
    }

    /**
     * Runs the analysis once and returns both its legacy JSON and the cropped PNG.
     *
     * @param mixed $source ImageSource, stream resource, raw image bytes, or GD image
     */
    public function process(mixed $source, ?AnalyzerOptions $options = null): ProcessedImageResult
    {
        $options ??= new AnalyzerOptions();
        $analysis = $this->run($source, $options);
        $crop = $analysis['crop'];

        return new ProcessedImageResult(
            $this->toJson($analysis['colors']),
            $this->pngEncoder->encode($crop->raster),
            $crop->boundingBox,
            $crop->wasCropped,
        );
    }

    public function processPath(string $path, ?AnalyzerOptions $options = null): ProcessedImageResult
    {
        return $this->process($this->sourceResolver->resolvePath($path), $options);
    }

    /**
     * @param mixed $source ImageSource, stream resource, raw image bytes, or GD image
     * @return array{
     *     colors: list<array{color:string,coverage_percent:float}>,
     *     crop: CropResult
     * }
     */
    private function run(mixed $source, AnalyzerOptions $options): array
    {
        $raster = $this->loader->load($this->sourceResolver->resolve($source));
        $crop = $this->cropper->crop($raster, $options->crop);
        $clusters = $this->clusterer->cluster($crop->raster, $options->cluster);

        $colors = [];
        foreach ($this->coverage->calculate($clusters) as $item) {
            $colors[] = $item->toArray();
        }

        return ['colors' => $colors, 'crop' => $crop];
    }

    /**
     * Encodes a coverage list as pretty JSON. `JSON_PRESERVE_ZERO_FRACTION`
     * keeps whole-number percentages as floats ("100.0", not "100") so every
     * `coverage_percent` renders with the documented one-decimal shape.
     *
     * @param list<array{color:string,coverage_percent:float}> $colors
     */
    private function toJson(array $colors): string
    {
        return (string) json_encode(
            $colors,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION,
        );
    }
}
