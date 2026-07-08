<?php


/**
 * Обвивка (service facade) над венднатата библиотека image-color-analyzer.
 *
 * Единствената входна точка към библиотеката: регистрира PSR-4 автолоудъра,
 * превежда конфигурацията IMGCOLOR_* към AnalyzerOptions, избира loader-а,
 * делегира към публичния facade и превежда изключенията на библиотеката към
 * native BGERP грешки (expect()).
 *
 * Библиотеката НЕ се променя - виж imgcolor/lib/.../VENDORED.md.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.1
 */
class imgcolor_Analyzer extends core_Mvc
{
    /**
     * Заглавие
     */
    public $title = 'Анализ на цветове за печат';


    /**
     * Дали PSR-4 автолоудърът е регистриран
     */
    private static $autoloadReady = false;


    /**
     * Инициализация на услугата
     */
    public function init()
    {
        self::registerAutoload();
    }


    /**
     * Регистрира PSR-4 автолоудър за namespace-а ImageColorAnalyzer\.
     * Идемпотентно; prepend, за да не се намесва native автолоудърът на BGERP.
     */
    public static function registerAutoload()
    {
        if (self::$autoloadReady) {

            return;
        }

        $base = __DIR__ . '/lib/image-color-analyzer/src/';

        spl_autoload_register(function ($class) use ($base) {
            $prefix = 'ImageColorAnalyzer\\';
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {

                return;
            }

            $rel = substr($class, strlen($prefix));
            $file = $base . str_replace('\\', '/', $rel) . '.php';
            if (is_file($file)) {
                include_once $file;
            }
        }, true, true);

        self::$autoloadReady = true;
    }


    /**
     * Гарантира наличието на GD (задължително за всички loader-и).
     */
    private static function requireGd()
    {
        expect(extension_loaded('gd'), 'imgcolor: PHP разширението GD е задължително');
    }


    /**
     * Изгражда AnalyzerOptions от конфигурацията IMGCOLOR_*.
     *
     * @return \ImageColorAnalyzer\Options\AnalyzerOptions
     */
    public static function buildOptions()
    {
        self::registerAutoload();

        $crop = new \ImageColorAnalyzer\Options\CropOptions(
            (float) imgcolor_Setup::get('CROP_LIGHTNESS_MIN'),
            (float) imgcolor_Setup::get('CROP_CHROMA_MAX'),
            (float) imgcolor_Setup::get('CROP_LINE_CONTENT_FRACTION'),
            (int) imgcolor_Setup::get('CROP_ALPHA_THRESHOLD')
        );

        $fixedK = imgcolor_Setup::get('CLUSTER_FIXED_K');
        $fixedK = ($fixedK === null || $fixedK === '' || (int) $fixedK < 1) ? null : (int) $fixedK;

        $cluster = new \ImageColorAnalyzer\Options\ClusterOptions(
            $fixedK,
            (int) imgcolor_Setup::get('CLUSTER_KMAX'),
            (int) imgcolor_Setup::get('CLUSTER_HISTOGRAM_BITS'),
            (float) imgcolor_Setup::get('CLUSTER_MERGE_DELTAE'),
            (float) imgcolor_Setup::get('CLUSTER_MIN_COVERAGE'),
            (int) imgcolor_Setup::get('CLUSTER_SEED'),
            (int) imgcolor_Setup::get('CLUSTER_ALPHA_THRESHOLD')
        );

        return new \ImageColorAnalyzer\Options\AnalyzerOptions($crop, $cluster);
    }


    /**
     * Създава инстанция на библиотечния facade според избрания loader.
     *
     * @return \ImageColorAnalyzer\PublicAPI\ImageColorAnalyzer
     */
    public static function makeAnalyzer()
    {
        self::registerAutoload();

        if (imgcolor_Setup::get('LOADER') === 'imagick') {
            $converter = new \ImageColorAnalyzer\Color\ColorConverter();

            return new \ImageColorAnalyzer\PublicAPI\ImageColorAnalyzer(
                new \ImageColorAnalyzer\ImageLoader\ImagickImageLoader(),
                new \ImageColorAnalyzer\WhiteBackgroundCropper\WhiteBackgroundCropper($converter),
                new \ImageColorAnalyzer\ColorClusterer\KMeansClusterer($converter, new \ImageColorAnalyzer\ColorClusterer\ColorHistogram(), new \ImageColorAnalyzer\ColorClusterer\KSelector($converter)),
                new \ImageColorAnalyzer\CoverageCalculator\PercentageCoverageCalculator(),
                null,
                new \ImageColorAnalyzer\ImageEncoder\GdPngEncoder()
            );
        }

        return \ImageColorAnalyzer\PublicAPI\AnalyzerFactory::createDefault();
    }


    /**
     * Общ guard: автолоуд + GD + превод на изключенията.
     *
     * @param callable $fn - получава изградения библиотечен facade
     *
     * @return mixed
     */
    private static function guard($fn)
    {
        self::registerAutoload();
        self::requireGd();

        try {

            return $fn(self::makeAnalyzer());
        } catch (\ImageColorAnalyzer\Exception\ImageAnalyzerException $e) {
            self::logWrite('Грешка при анализ на цветове: ' . $e->getMessage());
            expect(false, 'imgcolor: ' . $e->getMessage());
        }
    }


    /**
     * @param mixed $source ImageSource, stream, raw bytes или GD image
     *
     * @return array
     */
    public static function analyze($source, $options = null)
    {
        return self::guard(function ($a) use ($source, $options) {

            return $a->analyze($source, $options === null ? self::buildOptions() : $options);
        });
    }


    /**
     * @return array
     */
    public static function analyzePath($path, $options = null)
    {
        return self::guard(function ($a) use ($path, $options) {

            return $a->analyzePath($path, $options === null ? self::buildOptions() : $options);
        });
    }


    /**
     * @return string JSON
     */
    public static function analyzeAsJson($source, $options = null)
    {
        return self::guard(function ($a) use ($source, $options) {

            return $a->analyzeAsJson($source, $options === null ? self::buildOptions() : $options);
        });
    }


    /**
     * @return string JSON
     */
    public static function analyzePathAsJson($path, $options = null)
    {
        return self::guard(function ($a) use ($path, $options) {

            return $a->analyzePathAsJson($path, $options === null ? self::buildOptions() : $options);
        });
    }


    /**
     * @return \ImageColorAnalyzer\PublicAPI\ProcessedImageResult
     */
    public static function process($source, $options = null)
    {
        return self::guard(function ($a) use ($source, $options) {

            return $a->process($source, $options === null ? self::buildOptions() : $options);
        });
    }


    /**
     * @return \ImageColorAnalyzer\PublicAPI\ProcessedImageResult
     */
    public static function processPath($path, $options = null)
    {
        return self::guard(function ($a) use ($path, $options) {

            return $a->processPath($path, $options === null ? self::buildOptions() : $options);
        });
    }


    /**
     * LLM tool входна точка: анализира изображение по fileman handle.
     *
     * @param string $fh - fileman манипулатор на PNG/JPEG файл
     *
     * @return string JSON: [{"color":"#RRGGBB","coverage_percent":float}, ...]
     */
    public static function analyzeFileHandle($fh, $options = null)
    {
        self::registerAutoload();
        self::requireGd();

        expect($fRec = fileman_Files::fetchByFh($fh), 'imgcolor: липсва файл за подадения handle');
        $bytes = fileman::extractStr($fh);
        expect(is_string($bytes) && $bytes !== '', 'imgcolor: празно съдържание на файла');

        return self::guard(function ($a) use ($bytes, $options) {

            return $a->analyzeAsJson($bytes, $options === null ? self::buildOptions() : $options);
        });
    }


    /**
     * Self-describing tool descriptor за LLM function-calling.
     *
     * @return array
     */
    public static function getToolDefinition()
    {
        return array(
            'name' => 'analyze_image_print_colors',
            'description' => 'Crop the near-white background of a PNG/JPEG stored in fileman and '
                           . 'return the principal print colors with coverage percentages.',
            'parameters' => array(
                'type' => 'object',
                'properties' => array(
                    'fileHandle' => array(
                        'type' => 'string',
                        'description' => 'fileman handle (fh) of the image to analyze',
                    ),
                ),
                'required' => array('fileHandle'),
            ),
        );
    }
}
