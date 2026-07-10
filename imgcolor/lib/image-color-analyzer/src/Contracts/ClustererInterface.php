<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Contracts;

use ImageColorAnalyzer\Options\ClusterOptions;

interface ClustererInterface
{
    public function cluster(Raster $image, ClusterOptions $options): ClusterResult;
}
