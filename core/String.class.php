<?php



/**
 * Клас 'core_String' ['str'] - Функции за за работа със стрингове
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_String
{
    
    
    /**
     * Конвертира всички европейски азбуки,
     * включително и кирилицата, но без гръцката към латиница
     *
     * @param  string $text текст за конвертиране
     * @return string резултат от конвертирането
     * @access public
     */
    static function utf2ascii($text)
    {
        static $trans = array();
        
        if (!count($trans)) {
            ob_start();
            require_once(dirname(__FILE__) . '/transliteration.inc.php');
            ob_end_clean();
            
            $trans = $code;
        }
        
        foreach ($trans as $alpha => $lat) {
            $text = str_replace($alpha, $lat, $text);
        }
        
        preg_match_all('/[A-Z]{2,3}[a-z]/', $text, $matches);
        
        foreach ($matches[0] as $upper) {
            $cap = ucfirst(strtolower($upper));
            $text = str_replace($upper, $cap, $text);
        }
        
        return $text;
    }
    
    
    /**
     * Функция за генериране на случаен низ. Приема като аргумент шаблон за низа,
     * като символите в шаблона имат следното значение:
     *
     * '*' - Произволна латинска буква или цифра
     * '#' - Произволна цифра
     * '$' - Произволна буква
     * 'a' - Произволна малка буква
     * 'А' - Произволна голяма буква
     * 'd' - Малка буква или цифра
     * 'D' - Голяма буква или цифра
     */
    static function getRand($pattern = 'addddddd')
    {
        static $chars, $len;
        
        if(empty($chars)) {
            $chars['*'] = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['#'] = "0123456789";
            $chars['$'] = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['a'] = "abcdefghijklmnopqrstuvwxyz";
            $chars['A'] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['d'] = "0123456789abcdefghijklmnopqrstuvwxyz";
            $chars['D'] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            
            // Генерираме $seed
            $seed = microtime() . EF_SALT;
            
            foreach($chars as $k => $str) {
                
                $r2 = $len[$k] = strlen($str);
                
                while($r2 > 0) {
                    $r1 = (abs(crc32($seed . $r2--))) % $len[$k];
                    $c = $chars[$k]{$r1};
                    $chars[$k]{$r1} = $chars[$k]{$r2};
                    $chars[$k]{$r2} = $c;
                }
            }
        }
        
        $pLen = strlen($pattern);
        
        for($i = 0; $i < $pLen; $i++) {
            
            $p = $pattern{$i};
            
            $rand = rand(0, $len[$p]-1);
            
            $rand1 = ($rand + 7) % $len[$p];
            
            $c = $chars[$p]{$rand};
            $chars[$p]{$rand} = $chars[$p]{$rand1};
            $chars[$p]{$rand1} = $c;
            
            $res .= $c;
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function cut($str, $beginMark, $endMark = '', $caseSensitive = FALSE)
    {
        if (!$caseSensitive) {
            $sample = mb_strtolower($str);
            $beginMark = mb_strtolower($beginMark);
            $endMark = mb_strtolower($endMark);
        } else {
            $sample = $str;
        }
        
        $begin = mb_strpos($sample, $beginMark);
        
        if ($begin === FALSE) return;
        
        $begin = $begin + mb_strlen($beginMark);
        
        if ($endMark) {
            $end = mb_strpos($str, $endMark, $begin);
            
            if ($end === FALSE) return;
            
            $result = mb_substr($str, $begin, $end - $begin);
        } else {
            $result = mb_substr($str, $begin);
        }
        
        return $result;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function findOn($str, $match, $until = -1)
    {
        $str = mb_strtolower($str);
        $match = mb_strtolower($match);
        $find = mb_strpos($str, $match);
        
        if ($find === FALSE)
        return FALSE;
        
        if ($until < 0)
        return TRUE;
        
        if ($find <= $until)
        return TRUE;
        else
        return FALSE;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function addHash($str, $length = 4)
    {
        
        return $str . "_" . substr(md5(EF_SALT . $str), 0, $length);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function checkHash($str, $length = 4)
    {
        if ($str == str::addHash(substr($str, 0, strlen($str) - $length - 1), $length) && substr($str, -1 - $length, 1) == "_") {
            return substr($str, 0, strlen($str) - $length - 1);
        }
        
        return FALSE;
    }
    
    
    /**
     * Конвертиране между PHP и MySQL нотацията
     */
    static function phpToMysqlName($name)
    {
        $name = trim($name);
        
        for ($i = 0; $i < strlen($name); $i++) {
            $c = $name{$i};
            
            if ((($lastC >= "a" && $lastC <= "z") || ($lastC >= "0" && $lastC <= "9")) && ($c >= "A" && $c <= "Z")) {
                $mysqlName .= "_";
            }
            $mysqlName .= $c;
            $lastC = $c;
        }
        
        return strtolower($mysqlName);
    }
    
    
    /**
     * Превръща mysql име (с подчертавки) към нормално име
     */
    static function mysqlToPhpName($name)
    {
        $cap = FALSE;
        
        for ($i = 0; $i < strlen($name); $i++) {
            $c = $name{$i};
            
            if ($c == "_") {
                $cap = TRUE;
                continue;
            }
            
            if ($cap) {
                $out .= strtoupper($c);
                $cap = FALSE;
            } else {
                $out .= strtolower($c);
            }
        }
        
        return $out;
    }
    
    
    /**
     * Конвертира стринг до уникален стринг с дължина, не по-голяма от указаната
     * Уникалността е много вероятна, но не 100% гарантирана ;)
     */
    static function convertToFixedKey($str, $length = 64, $md5Len = 32, $separator = "_")
    {
        if (strlen($str) <= $length) return $str;
        
        $strLen = $length - $md5Len - strlen($separator);
        
        if ($strlen < 0)
        error("Дължината на MD5 участъка и разделителя е по-голяма от зададената обща дължина", array(
                'length' => $length,
                'md5Len' => $md5Len
            ));
        
        if (ord(substr($str, $strLen - 1, 1)) >= 128 + 64) {
            $strLen--;
            $md5Len++;
        }
        
        $md5 = substr(md5(_SALT_ . $str), 0, $md5Len);
        
        return substr($str, 0, $strLen) . $separator . $md5;
    }
    
    
    /**
     * Парсира израз, където променливите започват с #
     */
    static function prepareExpression($expr, $nameCallback)
    {
        $len = strlen($expr);
        $esc = FALSE;
        $isName = FALSE;
        $lastChar = '';
        
        for ($i = 0; $i <= $len; $i++) {
            $c = $expr{$i};
            
            if ($c == "'" && $lastChar != "\\") {
                $esc = (!$esc);
            }
            
            if ($esc) {
                $out .= $c;
                $lastChar = $c;
                continue;
            }
            
            if ($isName) {
                if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || $c == '_') {
                    $name .= $c;
                    continue;
                } else {
                    // Край на името
                    $isName = FALSE;
                    $out .= call_user_func($nameCallback, $name);
                    $out .= $c;
                    $lastChar = $c;
                    continue;
                }
            } else {
                if ($c == '#') {
                    $name = '';
                    $isName = TRUE;
                    continue;
                } else {
                    $out .= $c;
                    $lastChar = $c;
                }
            }
        }
        
        return $out;
    }
    
    
    /**
     * Проверка дали символът е латинска буква
     */
    static function isLetter($c)
    {
        
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || $c == '_';
    }
    
    
    /**
     * Проверка дали символът е цифра
     */
    static function isDigit($c)
    {
        return $c >= '0' && $c <= '9';
    }
    
    
    /**
     * По-добро премахване на white space
     */
    static function trim($s)
    {   
        $s = str_replace("&nbsp;", ' ', $s);

        $len = mb_strlen($s);

        for($i = 0; $i < $len; $i++) {

            $c = mb_substr($s, $i, 1); 

            if($c == chr(194) || $c == chr(160)) $c = ' ';

            $s1 .= $c;
        }
        return trim($s1);
    }
    
    
    /**
     * На по-големите от дадена дължина стрингове, оставя началото и края, а по средата ...
     */
    static function limitLen($str, $maxLen)
    {
        if(mb_strlen($str) > $maxLen) {
            if($maxLen > 20) {
                $remain = (int) ($maxLen - 5) / 2;
                $str = mb_substr($str, 0, $remain) . ' ... ' . mb_substr($str, -$remain);
            } else {
                $remain = (int) ($maxLen - 3);
                $str = mb_substr($str, 0, $remain) . ' ... ';
            }
        }
        
        return $str;
    }

}