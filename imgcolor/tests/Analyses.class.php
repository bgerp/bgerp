<?php


/**
 * Тестове за imgcolor_Analyses - програмно създаване и рендиране на записи.
 *
 * Пуска се през уеб контролера unit_Tests (?Ctr=unit_Tests), не през CLI.
 *
 * @package imgcolor
 */
class imgcolor_tests_Analyses extends unit_Class
{
    /**
     * createFromResult() записва резултата и връща валидно id
     */
    public static function test_CreateFromResultPersists($us)
    {
        $fh = fileman::absorb(dirname(__DIR__) . '/tests/fixtures/sample.png', 'imgcolorImages');
        $colorsJson = '[{"color":"#BE1E8C","coverage_percent":100.0}]';

        $id = imgcolor_Analyses::createFromResult($fh, null, $colorsJson);
        ut::expectEqual(true, $id > 0);

        $rec = imgcolor_Analyses::fetchRec($id);
        ut::expectEqual($colorsJson, $rec->colorsJson);
        ut::expectEqual(true, empty($rec->profileId));

        imgcolor_Analyses::delete($id);
    }


    /**
     * renderRec() произвежда същия HTML като renderColorsHtml() директно
     */
    public static function test_RenderRecMatchesDirectRender($us)
    {
        $fh = fileman::absorb(dirname(__DIR__) . '/tests/fixtures/sample.png', 'imgcolorImages');
        $colorsJson = '[{"color":"#BE1E8C","coverage_percent":100.0}]';

        $id = imgcolor_Analyses::createFromResult($fh, null, $colorsJson);
        $rec = imgcolor_Analyses::fetchRec($id);

        ut::expectEqual(imgcolor_Demo::renderColorsHtml($colorsJson), imgcolor_Analyses::renderRec($rec));

        imgcolor_Analyses::delete($id);
    }
}
