<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Options;

/**
 * Top-level options passed through the public facade to each stage.
 */
final readonly class AnalyzerOptions
{
    public function __construct(
        public CropOptions $crop = new CropOptions(),
        public ClusterOptions $cluster = new ClusterOptions(),
    ) {
    }
}
