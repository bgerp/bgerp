<?php

/**
 * Клас 'lang_Encoding' - Откриване на енкодинга и езика на текст
 *
 * Библиотека с функции за откриване на енкодинга и езика на стринг
 *
 * @category   Experta Framework
 * @package    lang
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @since      v 0.1
 */
class lang_Encoding {

    /**
     * Сурова информация за символните кодировки
     * Източник: http://asis.epfl.ch/GNU.MISC/recode-3.6/recode_6.html
     */
    static $charsets = array(
        
        // General character sets
        'US-ASCII' => 'ASCII, ISO646-US, ISO_646.IRV:1991, ISO-IR-6, ANSI_X3.4-1968, CP367, IBM367, US, csASCII, ISO646.1991-IRV, ASCI', 

        // General multi-byte encodings
        'UTF-8' => 'UTF8,UTF',
        'UCS-2' => 'ISO-10646-UCS-2',
        'UCS-2BE' => 'UNICODEBIG, UNICODE-1-1, csUnicode11',
        'UCS-2LE' => 'UNICODELITTLE',
        'UCS-4' => 'ISO-10646-UCS-4, csUCS4',
        'UCS-4BE',
        'UCS-4LE',
        'UTF-16',
        'UTF-16BE',
        'UTF-16LE',
        'UTF-7' => 'UNICODE-1-1-UTF-7, csUnicode11UTF7',
        'UCS-2-INTERNAL',
        'UCS-2-SWAPPED',
        'UCS-4-INTERNAL',
        'UCS-4-SWAPPED',
        'JAVA',

        // Standard 8-bit encodings
        'ISO-8859-10' => '8859-10, ISO_8859-10:1992, ISO-IR-157, LATIN6, L6, csISOLatin6, ISO8859-10', 
        'ISO-8859-13' => '8859-13, ISO-IR-179, LATIN7, L7', 
        'ISO-8859-14' => '8859-14, ISO_8859-14:1998, ISO-IR-199, LATIN8, L8', 
        'ISO-8859-15' => '8859-15, ISO_8859-15:1998, ISO-IR-203', 
        'ISO-8859-16' => '8859-16, ISO_8859-16:2000, ISO-IR-226', 
        'ISO-8859-5' => 'ISO_8859-5, ISO_8859-5:1988, ISO-IR-144, CYRILLIC, csISOLatinCyrillic, 8859-5, 8859_5', 
        'ISO-8859-1' => 'ISO_8859-1, ISO_8859-1:1987, ISO-IR-100, CP819, IBM819, LATIN1, L1, csISOLatin1, ISO8859-1, 8859_1,8859-1,8859', 
        'ISO-8859-2' => 'ISO_8859-2, ISO_8859-2:1987, ISO-IR-101, LATIN2, L2, csISOLatin2, 8859-2, 8859_2', 
        'ISO-8859-3' => 'ISO_8859-3, ISO_8859-3:1988, ISO-IR-109, LATIN3, L3, csISOLatin3, 8859-3, 8859_3', 
        'ISO-8859-4' => 'ISO_8859-4, ISO_8859-4:1988, ISO-IR-110, LATIN4, L4, csISOLatin4, 8859-4, 8859_4', 
        'ISO-8859-6' => 'ISO_8859-6, ISO_8859-6:1987, ISO-IR-127, ECMA-114, ASMO-708, ARABIC, csISOLatinArabic, 8859-6, 8859_6', 
        'ISO-8859-7' => 'ISO_8859-7, ISO_8859-7:1987, ISO-IR-126, ECMA-118, ELOT_928, GREEK8, GREEK, csISOLatinGreek, 8859-7, 8859_7', 
        'ISO-8859-8' => 'ISO_8859-8, ISO_8859-8:1988, ISO-IR-138, HEBREW, csISOLatinHebrew, ISO8859-8, 8859_8', 
        'ISO-8859-9' => 'ISO_8859-9, ISO_8859-9:1989, ISO-IR-148, LATIN5, L5, csISOLatin5, ISO8859-9, 8859_9', 
        'KOI8-R' => 'csKOI8R,KOI8R', 
        'KOI8-U',
        'KOI8-RU',

        // Windows 8-bit encodings
        'CP1250' => '1250, MS-EE', 
        'CP1251' => '1251, MS-CYRL, WINDOWS-BG, WIN-BG', 
        'CP1252' => '1252, MS-ANSI', 
        'CP1253' => '1253, MS-GREEK', 
        'CP1254' => '1254, MS-TURK', 
        'CP1255' => '1255, MS-HEBR', 
        'CP1256' => '1256, MS-ARAB', 
        'CP1257' => '1257, WINBALTRIM', 
        'CP1258' => '1258', 

        // DOS 8-bit encodings
        'CP850' => 'IBM850, 850, csPC850Multilingual', 
        'CP866' => 'IBM866, 866, csIBM866', 

        // Macintosh 8-bit encodings
        'MacRoman' => 'Macintosh, MAC, csMacintosh', 
        'MacCentralEurope',
        'MacIceland',
        'MacCroatian',
        'MacRomania',
        'MacCyrillic',
        'MacUkraine',
        'MacGreek',
        'MacTurkish',
        'MacHebrew',
        'MacArabic',
        'MacThai',

        // Other platform specific 8-bit encodings
        'HP-ROMAN8' => 'ROMAN8, R8, csHPRoman8', 
        'NEXTSTEP',

        // Regional 8-bit encodings used for a single language
        'ARMSCII-8',
        'Georgian-Academy',
        'Georgian-PS',
        'MuleLao-1',
        'CP1133' => 'IBM-CP1133',
        'TIS-620' => 'TIS620, TIS620-0, TIS620.2529-1, TIS620.2533-0, TIS620.2533-1, ISO-IR-166', 
        'CP874' => 'WINDOWS-874',
        'VISCII' => 'VISCII1.1-1, csVISCII', 
        'TCVN' =>  'TCVN-5712, TCVN5712-1, TCVN5712-1:1993', 

        // CJK character sets (not documented)
        'JIS_C6220-1969-RO' => 'ISO646-JP, ISO-IR-14, JP, csISO14JISC6220ro', 
        'JIS_X0201' => 'JISX0201-1976, X0201, csHalfWidthKatakana, JISX0201.1976-0, JIS0201', 
        'JIS_X0208' => 'JIS_X0208-1983, JIS_X0208-1990, JIS0208, X0208, ISO-IR-87, csISO87JISX0208, JISX0208.1983-0, JISX0208.1990-0, JIS0208', 
        'JIS_X0212' => 'JIS_X0212.1990-0, JIS_X0212-1990, X0212, ISO-IR-159, csISO159JISX02121990, JISX0212.1990-0, JIS0212', 
        'GB_1988-80' => 'ISO646-CN, ISO-IR-57, CN, csISO57GB1988', 
        'GB_2312-80' => 'ISO-IR-58, csISO58GB231280, CHINESE, GB2312.1980-0', 
        'ISO-IR-165' => 'CN-GB-ISOIR165',
        'KSC_5601' => 'KS_C_5601-1987, KS_C_5601-1989, ISO-IR-149, csKSC56011987, KOREAN, KSC5601.1987-0, KSX1001:1992, 5601', 

        // CJK encodings
        'EUC-JP' => 'EUCJP, Extended_UNIX_Code_Packed_Format_for_Japanese, csEUCPkdFmtJapanese, EUC_JP', 
        'SJIS' => 'SHIFT_JIS, SHIFT-JIS, MS_KANJI, csShiftJIS', 
        'CP932',
        'ISO-2022-JP' => '2022JP, ISO2022JP', 
        'ISO-2022-JP-1' => '2022JP1',
        'ISO-2022-JP-2' => '2022JP2',
        'EUC-CN' => 'EUCCN, GB2312, CN-GB, csGB2312, EUC_CN', 
        'GBK' => 'CP936', 
        'GB18030',
        'ISO-2022-CN' => 'csISO2022CN, ISO2022CN', 
        'ISO-2022-CN-EXT',
        'HZ' => 'HZ-GB-2312', 
        'EUC-TW' => 'EUCTW, csEUCTW, EUC_TW', 
        'BIG5' => 'BIG-5, BIG-FIVE, BIGFIVE, CN-BIG5, csBig5', 
        'CP950',
        'BIG5HKSCS',
        'EUC-KR' => 'EUCKR, csEUCKR, EUC_KR', 
        'CP949' => 'UHC',
        'JOHAB' => 'CP1361', 
        'ISO-2022-KR' => 'csISO2022KR, ISO2022KR', 
        'CHAR',
        'WCHAR_T',
    );

    
    /**
     *  Mасив с ключове - алиаси на чарсетове и стойности - официални имена на чарсетове
     */
    static $charsetsMatchs = array();

    
    /**
     *
     */
    static $encodings = array(
        'QUOTED-PRINTABLE' => 'quoted-print,quoted,q',
        'Base64' => 'base,64',
        'x-uuencode' => 'uu',
        '7bit' => '7',
        'BinHex'
    );
 
    
    /**
     *  Mасив с ключове - алиаси на  и стойности - официални имена на кодировки за двоични данни
     */
    static $encodingsMatchs = array();


    /**
     * Подготвя масив с ключове - алиаси на чарсетове и стойности - официални имена на чарсетове
     * Масивът е подреден от по-дългите ключове към по-късите
     */
    private static function prepareCharsetMatchs()
    {
        if(count(self::$charsetsMatchs)) {
            return;
        }

        foreach(self::$charsets as $name => $al) {

            if( is_int($name) ) $name = $al;
            
            $name = strtoupper(trim($name));
            expect(!self::$charsetsMatchs[$name]);
            self::$charsetsMatchs[$name] = $name;

            foreach(explode(",", $al) as $a) {
                $a = strtoupper(trim($a));
                expect(!self::$charsetsMatchs[$а]);
                self::$charsetsMatchs[$a] = $name;
            }
        }

        uksort(self::$charsetsMatchs, 'lang_Encoding::sort');
    }


    /**
     * Подготвя масив с ключове - алиаси на кодиране на бинарни данни
     * Масивът е подреден от по-дългите ключове към по-късите
     */
    private static function prepareEncodingMatchs()
    {
        if(count(self::$encodingsMatchs)) {
            return;
        }

        foreach(self::$encodings as $name => $al) {

            if( is_int($name) ) $name = $al;
            
            $name = strtoupper(trim($name));
            expect(!self::$encodingsMatchs[$name]);
            self::$encodingsMatchs[$name] = $name;

            foreach(explode(",", $al) as $a) {
                $a = strtoupper(trim($a));
                expect(!self::$encodingsMatchs[$а]);
                self::$encodingsMatchs[$a] = $name;
            }
        }

        uksort(self::$encodingsMatchs, 'lang_Encoding::sort');
    }


    /**
     * Помощна функция за сортиране според дължината на ключа
     */
    private function sort($a, $b)
    {
        return strlen($b) - strlen($a);
    }


    /**
     *
     */
    function detectUtf($text)
    {
    }
    
    /**
     * Резултат - ascci, 8bit-non-latin, 8bit-latin, utf8
     */
    function getPossibleEncodings($text)
    {
        $encodings = array('BASE64' => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=\n\r\t",
                           'QUOTED-PRINTABLE' => '',
                           'X-UUENCODE' => "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_\n\r\t",
                           '7BIT' => ''
                          );
        
        // Проверка за BinHex4
        $pos = stripos($text, "BinHex 4");
        if(0 < $pos && $pos  < 40) {
            return array('BINHEX');
        }
                             
        $len = strlen($text);
        for($i=0; $i < $len; $i++) {
            
            $c = $text{$i};
            $cOrd = ord($c);

            foreach($encodings as $name => $allowedChars) {
                if ($name == '7BIT')  {
                    if($cOrd > 127) {

                        return '8BIT';
                    }
                } elseif ($name == 'QUOTED-PRINTABLE') {
                    if( !(($cOrd >= 32 && $cOrd <= 126) || $cOrd == 9 || $cOrd == 10 || $cOrd == 13) ) {

                        unset($encodings[$name]);
                    }

                } elseif(strpos($allowedChars, $c) === FALSE) {
                    unset($encodings[$name]);
                }
            }

            foreach($encodings as $name => $chars) {
                $res[] = $name;
            }

            return $res;
        }
    }

    
    function detectLanguage($text)
    {
    }


    /**
     * Опитва се да извлече име на познато кодиране на 
     * двоични данни от зададения стринг
     */
    function canonizeEncoding($encoding)
    {
        $encoding = strtoupper(trim($encoding));

        // TODO: Да се санитаризира
        
        self::prepareEncodingMatchs();
        
        if(self::$encodingsMatchs[$encoding]) {
            $findEncoding = $encoding;
        } else {
            foreach(self::$encodingsMatchs as $key => $name) {
                if(strpos($encoding, (string) $key) !== FALSE) {
                    $findEncoding = $name;
                    break;
                }
            }
        }

        return $findEncoding;
    }


    /**
     * Опитва се да извлече име на позната за iconv() 
     * име на кодировка на символи от зададения стринг
     */
    function canonizeCharset($charset)
    {   
        $charset = strtoupper(trim($charset));

        // TODO: Да се санитаризира
        
        self::prepareCharsetMatchs();
        
        if(self::$charsetsMatchs[$charset]) {
            $findCharset = $charset;
        } else {
            foreach(self::$charsetsMatchs as $key => $name) {
                if(strpos($charset, (string) $key) !== FALSE) {
                    $findCharset = $name;
                    break;
                }
            }
        }

        if(!$findCharset) {
            $findCharset = substr($charset, 0, 64);
        }

        // Ако функцията iconv разпознава $findCharset като кодова таблица, връщаме $findCharset
        if(iconv($findCharset, 'UTF-8', 'OK') == 'OK') {

            return $findCharset;
        }
        
        return FALSE;
    }
}