<?php


/**
 * bgERP-независим оркестратор на разделния анализ: изрязване (библиотечен
 * cropper) -> класификация на преливките -> клъстеризиране на плътните
 * цветове (непроменен библиотечен път; при липса на преливки оригиналният
 * растер се подава директно за байт-идентичен резултат) -> CMYK акумулация.
 *
 * Тества се директно с `php imgcolor/tests/cli_separation.php`.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_Separation
{
    /**
     * @param \ImageColorAnalyzer\Contracts\Raster        $raster      зареден (неизрязан) растер
     * @param \ImageColorAnalyzer\Options\AnalyzerOptions $options
     * @param array                                       $transParams прагове за imgcolor_TransitionClassifier
     * @param imgcolor_CmykConverter                      $converter
     *
     * @return stdClass {colors, cmyk, crop, classification}
     */
    public static function process($raster, $options, array $transParams, imgcolor_CmykConverter $converter)
    {
        $libConverter = new \ImageColorAnalyzer\Color\ColorConverter();

        $cropper = new \ImageColorAnalyzer\WhiteBackgroundCropper\WhiteBackgroundCropper($libConverter);
        $crop = $cropper->crop($raster, $options->crop);

        // BACKGROUND дефиницията следва прага на клъстеризирането, за да
        // съвпада с досегашното изключване на прозрачни пиксели.
        $transParams['alphaThreshold'] = $options->cluster->alphaThreshold;
        $classification = imgcolor_TransitionClassifier::classify($crop->raster, $transParams);

        $clusterInput = $classification->transitionCount > 0
            ? new imgcolor_MaskedRaster($crop->raster, $classification->mask)
            : $crop->raster;

        $clusterer = new \ImageColorAnalyzer\ColorClusterer\KMeansClusterer(
            $libConverter,
            new \ImageColorAnalyzer\ColorClusterer\ColorHistogram(),
            new \ImageColorAnalyzer\ColorClusterer\KSelector($libConverter)
        );
        $clusters = $clusterer->cluster($clusterInput, $options->cluster);

        $colors = array();
        $coverage = new \ImageColorAnalyzer\CoverageCalculator\PercentageCoverageCalculator();
        foreach ($coverage->calculate($clusters) as $item) {
            $colors[] = $item->toArray();
        }

        $result = new stdClass();
        $result->colors = $colors;
        $result->cmyk = imgcolor_CmykAccumulator::accumulate($crop->raster, $classification, $converter);
        if ($result->cmyk !== null) {
            // Действително използваните (нормализирани) прагове - за
            // одитируемост, както conversion блока на конвертора.
            $result->cmyk['classifier'] = $classification->params;
        }
        $result->crop = $crop;
        $result->classification = $classification;

        return $result;
    }
}
