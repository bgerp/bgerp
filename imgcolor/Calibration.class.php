<?php


/**
 * Съпоставяне на плосък масив от прагове с AnalyzerOptions на венднатата
 * библиотека. Без bgERP зависимост - нарочно, за да е тествано директно
 * с `php imgcolor/tests/cli_calibration.php`, без работещ bgERP инстанс
 * (по образец на tests/cli_parity.php).
 *
 * Единствено място, което съпоставя имената на праговете към конструкторите
 * на CropOptions/ClusterOptions - използвано и от imgcolor_Analyzer::buildOptions()
 * (глобална конфигурация), и от imgcolor_Profiles::on_BeforeSave() (валидация
 * на профил).
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.2
 */
class imgcolor_Calibration
{
    /**
     * Имена на праговете, в реда на CropOptions/ClusterOptions конструкторите.
     * Споделени между imgcolor_Setup::$configDescription и
     * imgcolor_Profiles::description() - единствената форма стойности,
     * която тази класа приема.
     */
    public static $fields = array(
        'cropLightnessMin',
        'cropChromaMax',
        'cropLineContentFraction',
        'cropAlphaThreshold',
        'clusterFixedK',
        'clusterKMax',
        'clusterHistogramBits',
        'clusterMergeDeltaE',
        'clusterMinCoverage',
        'clusterSeed',
        'clusterAlphaThreshold',
    );


    /**
     * Строи AnalyzerOptions от плосък масив стойности (от Setup константи
     * или от Profile запис - еднакво по форма). Границите на стойностите
     * се пазят от самата библиотека: конструкторите на CropOptions/
     * ClusterOptions хвърлят InvalidArgumentException при некоректна
     * стойност, с явно име на полето в съобщението.
     *
     * @param array $values асоциативен масив по имената в self::$fields
     *
     * @throws InvalidArgumentException ако стойност е извън допустимия диапазон
     *
     * @return \ImageColorAnalyzer\Options\AnalyzerOptions
     */
    public static function buildOptions(array $values)
    {
        self::requireLibrary();

        $fixedK = $values['clusterFixedK'];
        if ($fixedK === null || $fixedK === '' || (int) $fixedK === 0) {
            $fixedK = null;
        } else {
            $fixedK = (int) $fixedK;
        }

        $crop = new \ImageColorAnalyzer\Options\CropOptions(
            (float) $values['cropLightnessMin'],
            (float) $values['cropChromaMax'],
            (float) $values['cropLineContentFraction'],
            (int) $values['cropAlphaThreshold']
        );

        $cluster = new \ImageColorAnalyzer\Options\ClusterOptions(
            $fixedK,
            (int) $values['clusterKMax'],
            (int) $values['clusterHistogramBits'],
            (float) $values['clusterMergeDeltaE'],
            (float) $values['clusterMinCoverage'],
            (int) $values['clusterSeed'],
            (int) $values['clusterAlphaThreshold']
        );

        return new \ImageColorAnalyzer\Options\AnalyzerOptions($crop, $cluster);
    }


    /**
     * Зарежда единствено класовете Options от венднатата библиотека - без
     * пълния PSR-4 автолоудър и без bgERP bootstrap, за да остане тази
     * класа тествана самостоятелно.
     */
    private static function requireLibrary()
    {
        if (class_exists('ImageColorAnalyzer\\Options\\AnalyzerOptions', false)) {

            return;
        }

        $base = __DIR__ . '/lib/image-color-analyzer/src/Options/';
        require_once $base . 'CropOptions.php';
        require_once $base . 'ClusterOptions.php';
        require_once $base . 'AnalyzerOptions.php';
    }
}
