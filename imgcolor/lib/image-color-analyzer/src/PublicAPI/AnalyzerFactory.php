<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\PublicAPI;

use ImageColorAnalyzer\Color\ColorConverter;
use ImageColorAnalyzer\ColorClusterer\ColorHistogram;
use ImageColorAnalyzer\ColorClusterer\KMeansClusterer;
use ImageColorAnalyzer\ColorClusterer\KSelector;
use ImageColorAnalyzer\CoverageCalculator\PercentageCoverageCalculator;
use ImageColorAnalyzer\ImageEncoder\GdPngEncoder;
use ImageColorAnalyzer\ImageLoader\GdImageLoader;
use ImageColorAnalyzer\WhiteBackgroundCropper\WhiteBackgroundCropper;

/**
 * Convenience wiring of the default (GD-backed) analyzer.
 */
final class AnalyzerFactory
{
    public static function createDefault(): ImageColorAnalyzer
    {
        $converter = new ColorConverter();

        return new ImageColorAnalyzer(
            new GdImageLoader(),
            new WhiteBackgroundCropper($converter),
            new KMeansClusterer($converter, new ColorHistogram(), new KSelector($converter)),
            new PercentageCoverageCalculator(),
            pngEncoder: new GdPngEncoder(),
        );
    }
}
