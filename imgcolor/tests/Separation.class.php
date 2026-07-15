<?php


/**
 * Тестове за разделния анализ (CMYK отделяне на преливките)
 *
 * Пуска се през уеб контролера unit_Tests (?Ctr=unit_Tests), не през CLI.
 * Framework-free покритието на класификатора/акумулатора е в
 * tests/cli_separation.php.
 *
 * @package imgcolor
 */
class imgcolor_tests_Separation extends unit_Class
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
     * Изображение без преливки: разделният резултат е байт-идентичен с
     * legacy пътя и няма CMYK payload.
     */
    public static function test_SolidImageKeepsLegacyOutput($us)
    {
        $fixture = dirname(__DIR__) . '/tests/fixtures/sample.png';

        $result = imgcolor_Analyzer::processSeparated(file_get_contents($fixture));

        ut::expectEqual(imgcolor_Analyzer::analyzePathAsJson($fixture), $result->json);
        ut::expectEqual(null, $result->cmykJson);
    }


    /**
     * Изображение с преливка: CMYK payload със състав, сумиращ точно 100.0,
     * и отделено покритие на прехода.
     */
    public static function test_GradientProducesCmykPayload($us)
    {
        $result = imgcolor_Analyzer::processSeparated(self::createGradientPng());

        ut::expectEqual(true, is_string($result->cmykJson));
        $cmyk = json_decode($result->cmykJson, true);
        $sum = 0.0;
        foreach ($cmyk['composition_percent'] as $v) {
            $sum += $v;
        }
        ut::expectEqual(100.0, round($sum, 1));
        ut::expectEqual(true, $cmyk['transition_coverage_percent'] > 50);
        ut::expectEqual(true, isset($cmyk['conversion']['engine']));
    }


    /**
     * Историята пази cmykJson и го рендира; запис без CMYK не показва блока.
     */
    public static function test_HistoryRoundTripsCmykJson($us)
    {
        $fh = fileman::absorbStr(self::createGradientPng(), self::$bucket, 'gradient.png');

        $result = imgcolor_Analyzer::processSeparated(fileman::extractStr($fh));
        imgcolor_Demo::persistResult($fh, null, $result, imgcolor_Calibration::getDefaultValues());

        $query = imgcolor_Analyses::getQuery();
        $query->orderBy('#id', 'DESC');
        $rec = $query->fetch();

        ut::expectEqual($result->cmykJson, $rec->cmykJson);
        ut::expectEqual(true, strpos(imgcolor_Analyses::renderRec($rec), 'CMYK') !== false);

        // legacy-съвместимо извикване без cmykJson
        $id = imgcolor_Analyses::createFromResult($fh, null, '[]');
        $legacyRec = imgcolor_Analyses::fetch($id);
        ut::expectEqual(true, empty($legacyRec->cmykJson));
        ut::expectEqual(false, strpos((string) imgcolor_Analyses::renderRec($legacyRec), 'CMYK'));
    }


    /**
     * Профилен override на праговете влияе на разделния анализ.
     */
    public static function test_ProfileOverridesSuppressTransitions($us)
    {
        $png = self::createGradientPng();

        $profileRec = new stdClass();
        $profileRec->transNoiseDeltaE = 20.0;

        $result = imgcolor_Analyzer::processSeparated($png, null, imgcolor_Analyzer::getTransParams($profileRec));
        ut::expectEqual(null, $result->cmykJson);

        // без override преливката се отчита
        $result = imgcolor_Analyzer::processSeparated($png);
        ut::expectEqual(true, is_string($result->cmykJson));
        $cmyk = json_decode($result->cmykJson, true);
        ut::expectEqual(true, isset($cmyk['classifier']['noiseDeltaE']));
    }


    /**
     * LLM tool за разделен анализ: envelope с colors + cmyk.
     */
    public static function test_SeparatedFileHandleTool($us)
    {
        $fh = fileman::absorbStr(self::createGradientPng(), self::$bucket, 'tool-gradient.png');

        $envelope = json_decode(imgcolor_Analyzer::analyzeSeparatedFileHandle($fh), true);
        ut::expectEqual(true, is_array($envelope['colors']));
        ut::expectEqual(true, is_array($envelope['cmyk']));

        $def = imgcolor_Analyzer::getSeparatedToolDefinition();
        ut::expectEqual('analyze_image_print_colors_separated', $def['name']);
        ut::expectEqual(true, in_array('fileHandle', $def['parameters']['required'], true));

        // legacy tool контрактът остава непроменен
        $legacyDef = imgcolor_Analyzer::getToolDefinition();
        ut::expectEqual('analyze_image_print_colors', $legacyDef['name']);
    }


    /**
     * Създава PNG с хоризонтална преливка червено -> синьо.
     *
     * @return string PNG байтове
     */
    private static function createGradientPng()
    {
        $im = imagecreatetruecolor(128, 64);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        for ($x = 0; $x < 128; $x++) {
            $r = (int) round(200 + (30 - 200) * $x / 127);
            $g = (int) round(30 + (60 - 30) * $x / 127);
            $b = (int) round(30 + (200 - 30) * $x / 127);
            imageline($im, $x, 0, $x, 63, imagecolorallocatealpha($im, $r, $g, $b, 0));
        }

        ob_start();
        imagepng($im);

        return ob_get_clean();
    }
}
