<?php


/**
 * Unit тестове за разпознаване на телефонни номера
 *
 * @category  ef
 * @package   drdata
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 */
class drdata_tests_Phones extends unit_Class
{
    /**
     * Група цифри с 02 не трябва да се приема за начало на софийски номер
     */
    public static function test_GroupedMobileNumbers(drdata_Phones $Phones)
    {
        $cases = array(
            '+359 888 315 029' => '+359888315029',
            '0888 315 029' => '+359888315029',
            '+359 888 123 104' => '+359888123104',
            '+359 879 123 021' => '+359879123021',
            '+359 899 123 029' => '+359899123029',
        );

        foreach ($cases as $input => $expected) {
            ut::expectEqual(self::getParsedNumbers($Phones, $input), $expected);
        }
    }


    /**
     * Различните разделители трябва да запазват и двата пълни номера
     */
    public static function test_GroupedMobileLists(drdata_Phones $Phones)
    {
        foreach (array(', ', '; ', ' / ', ' \\ ', ' ') as $separator) {
            $input = '+359 888 315 029' . $separator . '+359 888 123 104';
            ut::expectEqual(self::getParsedNumbers($Phones, $input), '+359888315029, +359888123104');
        }

        ut::expectEqual(self::getParsedNumbers($Phones, '+359888315029, +359888123104'), '+359888315029, +359888123104');
        ut::expectEqual(self::getParsedNumbers($Phones, '+359 88 8315029, +359 88 8123104'), '+359888315029, +359888123104');
    }


    /**
     * Запазва разделянето на софийски номера, изписани само с интервали
     */
    public static function test_SofiaNumbersSeparatedBySpaces(drdata_Phones $Phones)
    {
        foreach (array('02 1234567 02 7654321', '02 123 45 67 02 765 43 21') as $input) {
            ut::expectEqual(self::getParsedNumbers($Phones, $input), '+35921234567, +35927654321');
        }
    }


    /**
     * Номер без изричен код наследява зададения или предходния регион
     */
    public static function test_SharedAreaCode(drdata_Phones $Phones)
    {
        ut::expectEqual(self::getParsedNumbers($Phones, '02 1234567, 7654321'), '+35921234567, +35927654321');
        ut::expectEqual(self::getParsedNumbers($Phones, '032 123456, 654321'), '+35932123456, +35932654321');
        ut::expectEqual(self::getParsedNumbers($Phones, '123456, 654321', '32'), '+35932123456, +35932654321');
    }


    /**
     * Съседни чуждестранни номера не трябва да се слепват в един дълъг номер
     */
    public static function test_ForeignNumbersSeparatedBySpaces(drdata_Phones $Phones)
    {
        ut::expectEqual(self::getParsedNumbers($Phones, '045 1234 045 5678', '45', '352'), '+352451234, +352455678');
    }


    /**
     * Непълните мобилни номера остават невалидни
     */
    public static function test_IncompleteMobileNumbers(drdata_Phones $Phones)
    {
        foreach (array('0888 315', '+359 888 315', '+359 888 315 029, +359 888 315') as $input) {
            ut::expectEqual(self::getParsedNumbers($Phones, $input), '');
        }
    }


    /**
     * Заобикаля постоянния кеш, за да се проверява самият парсер
     */
    private static function getParsedNumbers(drdata_Phones $Phones, $input, $areaCode = '', $countryCode = '359')
    {
        $numbers = array();
        foreach ((array) $Phones->parseTel($input, $countryCode, $areaCode, false) as $number) {
            $numbers[] = '+' . ($number->countryCode ?? '') . ($number->areaCode ?? '') . ($number->number ?? '');
        }

        return implode(', ', $numbers);
    }
}
