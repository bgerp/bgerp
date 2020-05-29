<?php


/**
 * Unit тестове за стрингове
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_tests_String extends unit_Class
{
    /**
     * Конвертира всички европейски азбуки,
     * включително и кирилицата, но без гръцката към латиница
     *
     * @param string $text текст за конвертиране
     *
     * @return string резултат от конвертирането
     * @access public
     */
    public static function test_Utf2ascii($text)
    {
        $originalText = 'Йо Екс Бъ Ия ЙО ЕКС БЪ ИЯ бъ ия йо екс э а б в г д е ж з и й к л м н о п р с т у ф х ц ч ш щ ъ ы ь ю я Э А Б В Г Д Е Ж З И Й К Л М Н О П Р С Т У Ф Х Ц Ч Ш Щ Ъ Ь Ы Ю Я ” À Á Â Ã Ä Å Æ Ç È É Ê Ë Ì Í Î Ï Ð Ñ Ò Ó Ô Õ Ö Ø Ù Ú Û Ü Ý Þ ß à á â ã ä å æ ç è é ê ë ì í î ï ð ñ ò ó ô õ ö ø ù ú û ü ý þ ÿ ѓ ѕ ј љ њ ќ џ Ѓ Ѕ Ј Љ Њ Ќ Џ';
        $expectedText = 'Yo Ex Bu Ia YO EX BU IA bu ia yo ex e a b v g d e zh z i y k l m n o p r s t u f h ts ch sh sht a yi y yu ya E A B V G D E Zh Z I Y K L M N O P R S T U F H Ts Ch Sh Sht A Y YI Yu Ya \" A A A A A A AE C E E E E I I I I TH N O O O O O O U U U U Y TH ss a a a a a a ae c e e e e i i i i th n o o o o o o u u u u y th y gj d j l n k dj GJ D J L N K Dj';
        ut::expectEqual(core_String::utf2ascii($originalText), $expectedText);
        
        $originalText = 'ТОВА е ТЕСТ ТЕСт ТЕст Тест тест test Test TEst TESt TEST';
        $expectedText = 'TOVA e TEST Test Test Test test test Test TEst TESt TEST';
        
        ut::expectEqual(core_String::utf2ascii($originalText), $expectedText);
        
        $originalText = 'TESTЙорданTEST TESTЕкскалибурTEST TESTБългарияTEST TEStТЕСт TEstТЕст'; // CyrLat
        $expectedText = 'TESTYordanTEST TESTExkaliburTEST TESTBulgariaTEST TEStTest TEstTest';
        
        ut::expectEqual(core_String::utf2ascii($originalText), $expectedText);
        
        $originalText = 'Здравейте, това е тест. Hello. Здравей и ти, TESt. Здравей, Test Тест';
        $expectedText = 'Zdraveyte, tova e test. Hello. Zdravey i ti, TESt. Zdravey, Test Test';
        
        ut::expectEqual(core_String::utf2ascii($originalText), $expectedText);
        
        $originalText = 'hello HEllo HeLLo. HELLO!!! HEllo?';
        $expectedText = 'hello HEllo HeLLo. HELLO!!! HEllo?';
        
        ut::expectEqual(core_String::utf2ascii($originalText), $expectedText);
    }
}
