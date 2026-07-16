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
        $calibration = imgcolor_Calibration::getDefaultValues();
        $calibration['clusterFixedK'] = null;

        $id = imgcolor_Analyses::createFromResult($fh, null, $colorsJson, null, $calibration);
        ut::expectEqual(true, $id > 0);

        $rec = imgcolor_Analyses::fetchRec($id);
        ut::expectEqual($colorsJson, $rec->colorsJson);
        ut::expectEqual(true, empty($rec->profileId));
        ut::expectEqual($calibration, json_decode($rec->calibrationJson, true));

        imgcolor_Analyses::delete($id);
    }


    /**
     * Профилът показва източника, а snapshot-ът пази действително приложените стойности
     */
    public static function test_ProfileAndSnapshotAreIndependent($us)
    {
        $profile = self::validProfile('ic-analysis');
        imgcolor_Profiles::save($profile);

        $fh = fileman::absorb(dirname(__DIR__) . '/tests/fixtures/sample.png', 'imgcolorImages');
        $colorsJson = '[{"color":"#BE1E8C","coverage_percent":100.0}]';
        $calibration = imgcolor_Calibration::getValues($profile);
        $calibration['cropLightnessMin'] = 90.0;

        $id = imgcolor_Analyses::createFromResult($fh, $profile->id, $colorsJson, null, $calibration);
        $rec = imgcolor_Analyses::fetchRec($id);

        ut::expectEqual($profile->id, $rec->profileId);
        ut::expectEqual(90.0, json_decode($rec->calibrationJson, true)['cropLightnessMin']);

        imgcolor_Analyses::delete($id);
        imgcolor_Profiles::delete($profile->id);
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
        ut::expectEqual(true, empty($rec->calibrationJson));

        imgcolor_Analyses::delete($id);
    }


    /**
     * persistResult() записва резултата с подадения profileId в историята
     */
    public static function test_PersistResultWritesHistory($us)
    {
        $fh = fileman::absorb(dirname(__DIR__) . '/tests/fixtures/sample.png', 'imgcolorImages');

        $result = new stdClass();
        $result->json = '[{"color":"#BE1E8C","coverage_percent":100.0}]';
        $result->croppedImage = null;
        $calibration = imgcolor_Calibration::getDefaultValues();

        imgcolor_Demo::persistResult($fh, null, $result, $calibration);

        $rec = imgcolor_Analyses::fetch(array("#imageFile = '[#1#]'", $fh));
        ut::expectEqual(true, (bool) $rec);
        ut::expectEqual($result->json, $rec->colorsJson);
        ut::expectEqual(true, empty($rec->profileId));
        ut::expectEqual($calibration, json_decode($rec->calibrationJson, true));

        if ($rec) {
            imgcolor_Analyses::delete($rec->id);
        }
    }


    /**
     * Валиден профил за тестове на връзката към историята
     */
    private static function validProfile($sysId)
    {
        $rec = (object) imgcolor_Calibration::getDefaultValues();
        $rec->sysId = $sysId;
        $rec->name = $sysId;

        return $rec;
    }
}
