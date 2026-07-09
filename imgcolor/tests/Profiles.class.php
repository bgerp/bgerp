<?php


/**
 * Тестове за imgcolor_Profiles - валидация на прагове при запис.
 *
 * Пуска се през уеб контролера unit_Tests (?Ctr=unit_Tests), не през CLI.
 *
 * @package imgcolor
 */
class imgcolor_tests_Profiles extends unit_Class
{
    /**
     * Валиден профил се записва без грешка
     */
    public static function test_ValidProfileSaves($us)
    {
        $rec = self::validRec('imgcolor-test-valid');
        imgcolor_Profiles::save($rec);

        ut::expectEqual(true, $rec->id > 0);

        imgcolor_Profiles::delete($rec->id);
    }


    /**
     * Праг извън допустимия диапазон се отхвърля с core_exception_Expect
     */
    public static function test_OutOfRangeThresholdIsRejected($us)
    {
        $rec = self::validRec('imgcolor-test-invalid');
        $rec->cropLightnessMin = 150.0;

        try {
            imgcolor_Profiles::save($rec);
            ut::expectEqual(true, false);
        } catch (core_exception_Expect $e) {
            ut::expectEqual(true, strpos($e->getMessage(), 'imgcolor:') === 0);
            ut::expectEqual(true, strpos($e->getMessage(), 'lightnessMin') !== false);
        }
    }


    /**
     * Празен clusterFixedK е валиден (означава автоматичен избор на k)
     */
    public static function test_EmptyFixedKIsValid($us)
    {
        $rec = self::validRec('imgcolor-test-autok');
        $rec->clusterFixedK = null;
        imgcolor_Profiles::save($rec);

        ut::expectEqual(true, $rec->id > 0);

        imgcolor_Profiles::delete($rec->id);
    }


    /**
     * Помощен метод: валиден профилен запис с текущите библиотечни дефолти
     *
     * @param string $sysId
     *
     * @return stdClass
     */
    private static function validRec($sysId)
    {
        $rec = new stdClass();
        $rec->sysId = $sysId;
        $rec->name = $sysId;
        $rec->cropLightnessMin = 95.0;
        $rec->cropChromaMax = 5.0;
        $rec->cropLineContentFraction = 0.002;
        $rec->cropAlphaThreshold = 8;
        $rec->clusterFixedK = null;
        $rec->clusterKMax = 8;
        $rec->clusterHistogramBits = 5;
        $rec->clusterMergeDeltaE = 3.0;
        $rec->clusterMinCoverage = 0.01;
        $rec->clusterSeed = 1;
        $rec->clusterAlphaThreshold = 8;

        return $rec;
    }
}
