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
        fileman_Buckets::createBucket(self::$bucket, 'Изображения за цветови анализ', '', '50MB', 'imgcolor,ceo,admin', 'imgcolor,ceo,admin');
    }


    /**
     * Дефолтната обвивка връща същия JSON като библиотеката с дефолти
     */
    public static function test_ParityWithLibraryDefaults($us)
    {
        $fixture = dirname(__DIR__) . '/tests/fixtures/sample.png';

        imgcolor_Analyzer::registerAutoload();
        $direct = \ImageColorAnalyzer\PublicAPI\AnalyzerFactory::createDefault()->analyzePathAsJson($fixture);
        $wrapped = imgcolor_Analyzer::analyzePathAsJson($fixture);

        ut::expectEqual($direct, $wrapped);
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
