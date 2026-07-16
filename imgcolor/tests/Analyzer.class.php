<?php


/**
 * Тестове за imgcolor_Analyzer - паритет с венднатата библиотека
 *
 * Пуска се през уеб контролера unit_Tests (?Ctr=unit_Tests), не през CLI.
 *
 * @package imgcolor
 */
class imgcolor_tests_Analyzer extends unit_Class
{
    /**
     * Името на тестовата кофа
     */
    public static $bucket = 'imgcolorImages';


    /**
     * Подготвя кофата, ако пакетът още не е инсталиран в тестовата среда.
     */
    public function __construct()
    {
        fileman_Buckets::createBucket(self::$bucket, 'Изображения за цветови анализ', 'jpg,jpeg,png', '50MB', 'imgcolor,ceo,admin', 'imgcolor,ceo,admin');
    }


    /**
     * Проверка на файла допуска само JPEG/PNG, без значение от регистъра на разширението
     */
    public static function test_FileEligibilityIsCaseInsensitive($us)
    {
        foreach (array('sample.jpg', 'sample.jpeg', 'sample.png', 'sample.JPG', 'sample.JpEg', 'sample.PNG') as $name) {
            ut::expectEqual(true, imgcolor_Analyzer::canAnalyzeFile($name));
        }

        foreach (array('sample.gif', 'sample.webp', 'sample.svg', 'sample', '') as $name) {
            ut::expectEqual(false, imgcolor_Analyzer::canAnalyzeFile($name));
        }

        $rec = (object) array('name' => 'record.JPEG');
        ut::expectEqual(true, imgcolor_Analyzer::canAnalyzeFile($rec));
    }


    /**
     * Дефолтната обвивка връща същия JSON като библиотеката с дефолти
     */
    public static function test_ParityWithLibraryDefaults($us)
    {
        $fixture = dirname(__DIR__) . '/tests/fixtures/sample.png';

        imgcolor_Analyzer::registerAutoload();
        $options = new \ImageColorAnalyzer\Options\AnalyzerOptions();
        $direct = \ImageColorAnalyzer\PublicAPI\AnalyzerFactory::createDefault()->analyzePathAsJson($fixture, $options);
        $wrapped = imgcolor_Analyzer::analyzePathAsJson($fixture, $options);

        ut::expectEqual($direct, $wrapped);
    }


    /**
     * buildOptions() приема профилен запис вместо глобалната конфигурация
     */
    public static function test_BuildOptionsFromProfileRecord($us)
    {
        $rec = new stdClass();
        $rec->cropLightnessMin = 90.0;
        $rec->cropChromaMax = 4.0;
        $rec->cropLineContentFraction = 0.003;
        $rec->cropAlphaThreshold = 10;
        $rec->clusterFixedK = 3;
        $rec->clusterKMax = 6;
        $rec->clusterHistogramBits = 4;
        $rec->clusterMergeDeltaE = 2.5;
        $rec->clusterMinCoverage = 0.02;
        $rec->clusterSeed = 7;
        $rec->clusterAlphaThreshold = 12;

        $options = imgcolor_Analyzer::buildOptions($rec);

        ut::expectEqual(90.0, $options->crop->lightnessMin);
        ut::expectEqual(6, $options->cluster->kMax);
        ut::expectEqual(3, $options->cluster->fixedK);

        // No argument: behavior is unchanged (reads global IMGCOLOR_* config)
        $default = imgcolor_Analyzer::buildOptions();
        ut::expectEqual((float) imgcolor_Setup::get('CROP_LIGHTNESS_MIN'), $default->crop->lightnessMin);
    }


    /**
     * Изображение с прозрачен фон анализира само плътното съдържание
     */
    public static function test_TransparentBackgroundIgnoresAlpha($us)
    {
        $fixture = dirname(__DIR__) . '/tests/fixtures/sample_transparent.png';
        $colors = json_decode(imgcolor_Analyzer::analyzePathAsJson($fixture), true);

        ut::expectEqual(1, countR($colors));
        ut::expectEqual('#BE1E8C', $colors[0]['color']);
        ut::expectEqual(100.0, $colors[0]['coverage_percent']);
    }


    /**
     * Напълно прозрачно изображение връща празен списък
     */
    public static function test_FullyTransparentYieldsEmpty($us)
    {
        $fixture = self::createTransparentFixture();

        try {
            ut::expectEqual('[]', imgcolor_Analyzer::analyzePathAsJson($fixture));
        } finally {
            if (is_file($fixture)) {
                unlink($fixture);
            }
        }
    }


    /**
     * analyzeFileHandle през fileman дава същия резултат като analyzePath
     */
    public static function test_FileHandleMatchesPath($us)
    {
        $fixture = dirname(__DIR__) . '/tests/fixtures/sample.png';
        $fh = fileman::absorb($fixture, self::$bucket);

        ut::expectEqual(
            imgcolor_Analyzer::analyzePathAsJson($fixture),
            imgcolor_Analyzer::analyzeFileHandle($fh)
        );

        $def = imgcolor_Analyzer::getToolDefinition();
        ut::expectEqual('analyze_image_print_colors', $def['name']);
        ut::expectEqual(true, in_array('fileHandle', $def['parameters']['required'], true));
    }


    /**
     * Грешките от библиотеката се виждат като съобщение, не само като debug dump
     */
    public static function test_InvalidImageKeepsAnalyzerMessage($us)
    {
        try {
            imgcolor_Analyzer::analyzeAsJson('not an image');
            ut::expectEqual(true, false);
        } catch (core_exception_Expect $e) {
            ut::expectEqual(true, strpos($e->getMessage(), 'imgcolor:') === 0);
            ut::expectEqual(true, strpos($e->getMessage(), 'PNG') !== false || strpos($e->getMessage(), 'JPEG') !== false);
        }
    }


    /**
     * Некоректни опции се отхвърлят явно, вместо да дават тихо грешни резултати
     */
    public static function test_InvalidOptionsAreRejected($us)
    {
        imgcolor_Analyzer::registerAutoload();

        try {
            new \ImageColorAnalyzer\Options\CropOptions(alphaThreshold: 300);
            ut::expectEqual(true, false);
        } catch (InvalidArgumentException $e) {
            ut::expectEqual(true, strpos($e->getMessage(), 'alphaThreshold') !== false);
        }

        try {
            new \ImageColorAnalyzer\Options\ClusterOptions(histogramBitsPerChannel: 9);
            ut::expectEqual(true, false);
        } catch (InvalidArgumentException $e) {
            ut::expectEqual(true, strpos($e->getMessage(), 'histogramBitsPerChannel') !== false);
        }
    }


    /**
     * Line-noise guard не трябва да отрязва реален тънък детайл по края
     */
    public static function test_CropKeepsThinEdgeContent($us)
    {
        imgcolor_Analyzer::registerAutoload();

        $white = new \ImageColorAnalyzer\Contracts\ColorRGBA(255, 255, 255);
        $black = new \ImageColorAnalyzer\Contracts\ColorRGBA(0, 0, 0);

        $pixels = array_fill(0, 25, $white);
        $pixels[0] = $black;
        foreach (array(18, 19, 23, 24) as $index) {
            $pixels[$index] = $black;
        }

        $raster = new \ImageColorAnalyzer\ImageLoader\InMemoryRaster(5, 5, $pixels);
        $cropper = new \ImageColorAnalyzer\WhiteBackgroundCropper\WhiteBackgroundCropper(
            new \ImageColorAnalyzer\Color\ColorConverter()
        );

        $crop = $cropper->crop($raster, new \ImageColorAnalyzer\Options\CropOptions(lineContentFraction: 0.3));

        ut::expectEqual(0, $crop->boundingBox->x);
        ut::expectEqual(0, $crop->boundingBox->y);
        ut::expectEqual(5, $crop->boundingBox->width);
        ut::expectEqual(5, $crop->boundingBox->height);
    }


    /**
     * Създава временна напълно прозрачна PNG картинка.
     *
     * @return string
     */
    private static function createTransparentFixture()
    {
        $path = tempnam(sys_get_temp_dir(), 'imgcolor-transparent-');
        expect($path !== false, 'Не може да се създаде временен файл за теста');

        $image = imagecreatetruecolor(4, 4);
        expect($image !== false, 'Не може да се създаде GD изображение за теста');

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        expect($transparent !== false, 'Не може да се създаде прозрачен цвят за теста');
        expect(imagefilledrectangle($image, 0, 0, 3, 3, $transparent), 'Не може да се запълни тестовото изображение');
        expect(imagepng($image, $path), 'Не може да се запише тестовото изображение');

        return $path;
    }
}
