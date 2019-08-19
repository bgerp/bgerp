<?php


/**
 * Клас 'lang_Encoding' - Откриване на енкодинга и езика на текст
 *
 * Библиотека с функции за откриване на енкодинга и езика на стринг
 *
 *
 * @category  vendors
 * @package   lang
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class i18n_Encoding
{
    /**
     *  Масив с ключове - алиас-и на  и стойности - официални имена на кодировки за двоични данни
     */
    public static $encodingsMatchs = array();
    
    
    /**
     * Резултат - aSCII, 8bit-non-latin, 8bit-latin, utf8
     */
    public function getPossibleEncodings($text)
    {
        $encodings = array('BASE64' => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=\n\r\t",
            'QUOTED-PRINTABLE' => '',
            'X-UUENCODE' => "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_\n\r\t",
            '7BIT' => ''
        );
        
        // Проверка за BinHex4
        $pos = stripos($text, 'BinHex 4');
        
        if (0 < $pos && $pos < 40) {
            
            return array('BINHEX');
        }
        
        $len = strlen($text);
        
        for ($i = 0; $i < $len; $i++) {
            $c = $text{$i};
            $cOrd = ord($c);
            
            foreach ($encodings as $name => $allowedChars) {
                if ($name == '7BIT') {
                    if ($cOrd > 127) {
                        
                        return '8BIT';
                    }
                } elseif ($name == 'QUOTED-PRINTABLE') {
                    if (!(($cOrd >= 32 && $cOrd <= 126) || $cOrd == 9 || $cOrd == 10 || $cOrd == 13)) {
                        unset($encodings[$name]);
                    }
                } elseif (strpos($allowedChars, $c) === false) {
                    unset($encodings[$name]);
                }
            }
            
            foreach ($encodings as $name => $chars) {
                $res[] = $name;
            }
            
            return $res;
        }
    }
    
    
    /**
     * Опитва се да извлече име на познато кодиране на
     * двоични данни от зададения стринг
     */
    public static function getCanonical($encoding)
    {
        $encoding = strtoupper(trim($encoding));
        
        if (!$encoding) {
            
            return;
        }
        
        self::prepareEncodingMatchs();
        
        if (self::$encodingsMatchs[$encoding]) {
            $findEncoding = $encoding;
        } else {
            foreach (self::$encodingsMatchs as $key => $name) {
                if (strpos($encoding, (string) $key) !== false) {
                    $findEncoding = $name;
                    break;
                }
            }
        }
        
        return $findEncoding;
    }
    
    
    /**
     * Подготвя масив с ключове - алиас-и на кодиране на бинарни данни
     * Масивът е подреден от по-дългите ключове към по-късите
     */
    private static function prepareEncodingMatchs()
    {
        if (count(self::$encodingsMatchs)) {
            
            return;
        }
        
        // Масив с най-често срещаните encoding-s
        $encodings = array(
            'QUOTED-PRINTABLE' => 'quoted-print,quoted,q',
            'BASE64' => 'base,64',
            'X-UUENCODE' => 'uu',
            '7BIT' => '7',
            '8BIT' => '8',
            'BINHEX'
        );
        
        foreach ($encodings as $name => $al) {
            if (is_int($name)) {
                $name = $al;
            }
            
            $name = strtoupper(trim($name));
            expect(!self::$encodingsMatchs[$name]);
            self::$encodingsMatchs[$name] = $name;
            
            $alArr = explode(',', $al);
            
            foreach ($alArr as $a) {
                $a = strtoupper(trim($a));
                
                if ($a != $name) {
                    expect(!self::$encodingsMatchs[$a]);
                }
                
                self::$encodingsMatchs[$a] = $name;
            }
        }
        
        uksort(self::$encodingsMatchs, 'i18n_Encoding::sort');
    }
    
    
    /**
     * Помощна функция за сортиране според дължината на ключа
     */
    private static function sort($a, $b)
    {
        return strlen($b) - strlen($a);
    }
}
