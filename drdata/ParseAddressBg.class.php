<?php

/**
 * Парсиране на български пощенски адреси
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_ParseAddressBg extends core_Manager
{
    /**
     * Подготва речника
     */
    private function prepareDict()
    {
        $csvData = file_get_contents(getFullPath('drdata/data/CITIES.csv'));
        $data = csv_Lib::getCsvRows($csvData, ',', ',', 'data');
        
        foreach ($data as $r) {
            $r[3] = mb_convert_case($r[3], MB_CASE_TITLE);
            self::pushPlace($res[$r[1]], $place = strtolower(str::utf2ascii($r[3])), $r[3]);
            self::pushPlace($res[$r[1]], strtolower(str::utf2ascii($r[4])), $r[3]);
        }
        
        foreach ($res['гр.'] as $city => $pcode) {
            $t1 = $t2 = '';
            if (strpos($city, ' ')) {
                $cArr = explode(' ', $city);
                foreach ($cArr as $i => $w) {
                    if ($i == count($cArr) - 1) {
                        $t1 .= ' ' . $w;
                        $t2 .= ' ' . $w;
                    } else {
                        $arrbs = self::getAbbrs($w);
                        if (isset($arrbs[0])) {
                            $t1 .= ' ' . $arrbs[0];
                        } else {
                            $t1 .= ' ' . $w;
                        }
                        if (isset($arrbs[1])) {
                            $t2 .= ' ' . $arrbs[1];
                        } elseif (isset($arrbs[0])) {
                            $t2 .= ' ' . $arrbs[0];
                        } else {
                            $t2 .= ' ' . $w;
                        }
                    }
                }
                self::pushPlace($res['гр.'], trim($t1), $pcode);
                self::pushPlace($res['гр.'], trim($t2), $pcode);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Добавя място в речника
     */
    public function pushPlace(&$arr, $place, $name)
    {
        $arr[$place] = $name;
    }
    
    
    /**
     * Съкращения на думи
     */
    public static function getAbbrs($w)
    {
        $len = strlen($w);
        $part = '';
        $res = array();
        
        for ($i = 0; $i < $len - 2; $i++) {
            $c = $w[$i];
            $part .= $c;
            $n = $w[$i + 1];
            if (self::isVowel($n) && !self::isVowel($c)) {
                $res[] = $part . '.';
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща дали дадена буква е гласна
     */
    public static function isVowel($c)
    {
        return strpos('aeouiy', $c) !== false;
    }
    
    
    /**
     * Тестова функция
     */
   /* public function act_Test()
    {
        requireRole('debug');
        $data = file_get_contents('C:/test/Addresses.txt');
        $arr = explode("\n", $data);
        
        foreach ($arr as $row) {
            list($country, $address) = explode('|', $row);
            if ($country != 'България') {
                continue;
            }
            
            $nAddress = self::parse($address, $dict);
            
            $res[$address] = $nAddress;
        }
        
        bp($res);
    } */
    
    
    /**
     * Връща вариациите и подфразите на дадена фраза в каноничен вид
     */
    public function getVariations($str)
    {
        $str = trim($str);
        
        $arr = explode(' ', $str);
        
        while (count($arr)) {
            // Вкарваме текущата фраза
            $p = implode(' ', $arr);
            $res[trim(strtolower(str::utf2ascii($p)))] = $p;
            if (count($arr) == 2) {
                $res[trim(strtolower(str::utf2ascii(implode('', $arr))))] = $p;
            }
            unset($arr[count($arr) - 1]);
        }
        
        return $res;
    }
    
    
    /**
     * Връща най-добрия резултат от речника за подмножествата на дадена фраза
     */
    public function getBestMatch($str, $dict)
    {
        $vars = self::getVariations($str);
        
        $bestKey = null;
        $bestDist = 1;
        
        foreach ($vars as $key => $name) {
            foreach ($dict as $type => $arr) {
                if (isset($arr[$key])) {
                    
                    return array($key, $name, $type, $arr[$key]);
                }
                $keyLen = strlen($key);
                foreach ($arr as $k => $v) {
                    if ($k{0} == $key{0} && abs($keyLen - strlen($k)) <= 1) {
                        $d = levenshtein($k, $key) / $keyLen;
                        if ($d < 0.15 && $d < $bestDist) {
                            $a = substr($k, -1) == 'o' || substr($k, -1) == 'о';
                            $b = substr($key, -1) == 'o' || substr($key, -1) == 'о';
                            if (!($a ^ $b)) {
                                $bestDist = $d;
                                $bestKey = $k;
                                $bestVal = $arr[$bestKey];
                                $bestType = $type;
                                $bestName = $name;
                            }
                        }
                    }
                }
            }
        }
        
        if ($bestKey) {
            
            return array($key, $bestName, $bestType, $bestVal);
        }
        
        return false;
    }
    
    
    /**
     * Изяжда, каквото може от $part, тъпче го в партс и връща остатъка
     */
    public static function eatPart($p, array &$parts, $dict)
    {
        $p = trim($p);
        $org = $p;
        
        // Опитваме се да извлечем познати части на адреса
        if (preg_match("/^(гр\.|c\.|ул\.|бул\.|пл.|ж\.к\.|бл\.|вх\.|ет\.|ап\.|обл\.|общ\.|кв.|офис|хотел|сграда|местност|р-н|в.с.|к.м.|лет.|с.ман.|т.ц.|ПК|п.код) ?(.+$)/u", $p, $matches)) {
            $parts[$p1 = $matches[1]] = trim(substr($p, strlen($matches[1])));
            
            // Нормализиране на уличен адрес
            if ($p1 == 'ул.' || $p1 == 'бул.' || $p1 == 'пл.') {
                if (preg_match('/^\\"?([a-zа-я\. ]{3,64})\\"?[ \-]{1,3}([0-9]{1,2}( ?[\-\/] ?[0-9]{1,2}|) ?[\-\/\\]? ?[a-zа-я]?)$/iu', $parts[$p1], $matches)) {
                    $parts[$p1] = $matches[1] . ' ' . $matches[2];
                }
            }
            
            if ($p1 == 'гр.' || $p1 == 'с.') {
                if ($b = self::getBestMatch($parts[$p1], $dict)) {
                    $rest = trim(substr($parts[$p1], strlen($b[1])));
                    $parts[$p1] = $b[3];
                    
                    return $rest;
                }
            }
            
            $p = '';
        
        // Ако започва със С. и след това има село, то изяждаме селото
        } elseif (!isset($parts['c.']) && !isset($parts['гр.']) && substr($p, 0, 2) == 'C.') {
            $p1 = substr($p, 2);
            if ($b = self::getBestMatch($p1, $dict)) {
                if ($b[2] == 'c.') {
                    $match = 'C. ' . $b[1];
                    $p = trim(substr($p, strlen($b[1]) + 3));
                    $parts['c.'] = $b[3];
                }
            }
            
            // Ако започва с познато място
        } elseif (!isset($parts['гр.']) && ($b = self::getBestMatch($p, $dict))) {
            $p = trim(substr($p, strlen($b[1])));
            if (!isset($parts[$b[2]])) {
                $parts[$b[2]] = $b[3];
            } else {
                if ($b[2] == 'гр.') {
                    if (!isset($parts['общ.'])) {
                        $parts['общ.'] = $b[3];
                    } elseif (!isset($parts['обл.'])) {
                        $parts['обл.'] = $b[3];
                    }
                }
            }
            
            // Ако започва с нещо като пощенски код
        } elseif (!isset($parts['п.код']) && preg_match('/^([0-9]{4})([^0-9]|)/', $p, $matches)) {
            $parts['п.код'] = $matches[1];
            $p = trim(substr($p, 4));
        } elseif (!isset($parts['ж.к.']) && preg_match("/((младост|люлин|надежда|обеля|връбница|зона б|сторгозия|дружба|красно Село) ?[ \-] ?[0-9]{0,2})/ui", $p, $matches)) {
            $parts['ж.к.'] = $matches[1];
            $p = trim(substr($p, strlen($matches[1])));
        } elseif (!isset($parts['ул.']) && !isset($parts['бул.']) && preg_match('/^\\"?([a-zа-я\. ]{3,64})\\"?[ \-]{1,3}([0-9]{1,3}( ?[\-\/] ?[0-9]{1,3}|)[a-zа-я]?)$/iu', $p, $matches)) {
            $parts['ул.'] = $matches[1] . ' ' . $matches[2];
            $p = '';
        } elseif (!isset($parts['ул.']) && !isset($parts['бул.']) && preg_match('/^(\\"[a-zа-я\. 0-9]{2,64}\\")[ \-]{1,3}([0-9]{1,3}[a-zа-я]?)$/iu', $p, $matches)) {
            $parts['ул.'] = $matches[1] . ' ' . $matches[2];
            $p = '';
        }
        
        if ($org == $p) {
            $parts[] = $p;
            $p = '';
        }
        
        return trim($p);
    }
    
    
    /**
     * Парсира адресен стринг. Основен метод
     */
    public static function parse($str)
    {
        static $dict;
        
        if (!$dict) {
            $dict = core_Cache::get('ParseAddress', 'Dictionary1');
            if (!$dict) {
                $dict = self::prepareDict();
                core_Cache::set('ParseAddress', 'Dictionary1', $dict, isDebug() ? 100 : 10000);
            }
        }
        
        // Форматиране на препинателни знаци
        $str = ' ' . trim(str_replace(array('№', '”', '„', '.', ',', ';', ':', '  ', '  ', ', ,'), array(' №', '"', '"', '. ', ', ', '; ', ': ', ' ', ' ', ', '), $str)) . ' ';
        
        // Грешно изписани начала на думи с латински букви
        $str = preg_replace_callback('/([^a-zа-я][aeotkm][а-я]{1,30}[^a-zа-я])/iu', function ($a) {
            
            return  str_ireplace(array('a', 'e', 'o', 't', 'k', 'm'), array('А', 'Е', 'О', 'Т', 'К', 'М'), $a[1]);
        }, $str);
        
        // Грешна употреба на З вместо 3
        $str = preg_replace('/([^a-zа-я])З-ти/iu', '$1 3-ти', $str);
        
        $str = mb_convert_case($str, MB_CASE_TITLE);
        
        // Подреждаме кавичките
        $partsArr = explode('"', $str);
        if (count($partsArr) == 3) {
            $str = rtrim($partsArr[0]) . ' "' .mb_convert_case(trim($partsArr[1]), MB_CASE_TITLE) . '" ' . ltrim($partsArr[2]);
        } elseif (count($partsArr) == 5) {
            $str = rtrim($partsArr[0]) . ' "' .mb_convert_case(trim($partsArr[1]), MB_CASE_TITLE) . '" ' . trim($partsArr[2]) .
            ' "' .mb_convert_case(trim($partsArr[3]), MB_CASE_TITLE) . '" ' . ltrim($partsArr[4]);
        }
        
        // Разделяне на някои изписани слято неща
        $str = preg_replace('/([^a-zа-я])(bl|бл|et|ет|ap|ап|ст|стая)([0-9])/iu', '$1$2 $3', $str);
        
        $str = preg_replace("/[^a-zа-я](жк.|ж\. ?к|ж|жк|ж-к|zh-k) ?\.? /iu", ', ж.к.', $str);
        $str = preg_replace("/[^a-zа-я](обл|област|obl|oblast) ?\.? /iu", ', обл.', $str);
        $str = preg_replace("/[^a-zа-я](ул|улица|ul|ulitsa|u-tsa|у-ца) ?\.? /iu", ', ул.', $str);
        $str = preg_replace("/[^a-zа-я](бул|булевард|bul) ?\.? /iu", ', бул.', $str);
        $str = preg_replace("/[^a-zа-я](гр|gr|\, град|^ град|\, grad|^ grad) ?\.? /iu", ', гр.', $str);
        $str = preg_replace("/^ ?(c\.?|С\.|s.) /iu", ', c.', $str);
        $str = preg_replace("/[^a-zа-я](кв|к-л|kv) ?\.? /iu", ', кв.', $str);
        $str = preg_replace("/[^a-zа-я](общ|община|об|ob|obsht|obshtina) ?\.? /iu", ', общ.', $str);
        $str = preg_replace("/[^a-zа-я](бл|bl) ?\.? /iu", ', бл.', $str);
        $str = preg_replace("/[^a-zа-я](ет|et|etazh|етаж) ?\.? /iu", ', ет.', $str);
        $str = preg_replace("/[^a-zа-я](вход|вх|vh|vhod|вx|бх) ?\.? /iu", ', вх.', $str);
        $str = preg_replace("/[^a-zа-я](апартамент|ап|апарт|ap|apart|apartament) ?[\.\:\-]? /iu", ', ап.', $str);
        $str = preg_replace("/[^a-zа-я](стая|staya|room) ?\.? /iu", ', стая ', $str);
        $str = preg_replace("/[^a-zа-я](офис|office|ofis|of\.|оф\.) ?\.? /iu", ', офис ', $str);
        $str = preg_replace("/[^a-zа-я](сграда|сгр|sgr|sgrada) ?\.? /iu", ', сграда ', $str);
        $str = preg_replace("/[^a-zа-я](хотел|х-л|hotel) ?\.? /iu", ', хотел ', $str);
        $str = preg_replace("/[^a-zа-я](р-н|район|rayon) ?\.? /iu", ', р-н ', $str);
        $str = preg_replace("/[^a-zа-я](местност|местн.|м-ност|м-ст) ?\.? /iu", ', местност ', $str);
        $str = preg_replace("/[^a-zа-я](в\. ?с|вс|v\. ?s) ?\.? /iu", ', в.с. ', $str);
        $str = preg_replace("/[^a-zа-я](к\. ?м|k\. ?m) ?\.? /iu", ', к.м. ', $str);
        $str = preg_replace("/[^a-zа-я](лет) ?\.? /iu", ', лет. ', $str);
        $str = preg_replace("/[^a-zа-я](с\. ?ман|s\. ?man) ?\.? /iu", ', с.ман. ', $str);
        $str = preg_replace("/[^a-zа-я](т\. ?ц) ?\.? /iu", ', т.ц. ', $str);
        $str = preg_replace("/[^a-zа-я](площад|пл)\.? /iu", ', пл. ', $str);
        $str = preg_replace("/([^a-zа-я])(п. ?к|пк|п к|pk|pb)\.? ?([0-9]{1,3})([^0-9])/iu", '$1, ПК $3$4', $str);
        $str = preg_replace("/([^a-zа-я])(п. ?к|пк|п к)\.? ?([0-9]{4})([^0-9])/iu", '$1, п.код $3$4', $str);
        
        // След тире се изписва с малки букви
        $str = preg_replace_callback('/([^a-zа-я" ]) ?- ?([a-zа-я]{2,10})([^a-zа-я])/iu', function ($a) {
            
            return  rtrim($a[1]) . '-' . ltrim(mb_strtolower($a[2])) . $a[3];
        }, $str);
        
        // Думи, които трябва да са с малки букви
        $str = preg_replace_callback("/([^a-zа-я])(инж\.?|проф\.?|пор\.?|акад\.?|ген\.?|д-р\.?|м-р\.?|в\/у|и)( )/iu", function ($a) {
            
            return  $a[1] . mb_strtolower($a[2]) . $a[3];
        }, $str);
        
        // Обработка на изписването на номерата
        $str = preg_replace('/[^a-zа-я](N ?|No.|Но.|#|№|N:) ?([0-9])/iu', ' $2', $str);
        
        // Премахване не тире, преди номера следващи кавички
        $str = preg_replace('/" ?- ?([0-9])/iu', '" $1', $str);
        
        $str = preg_replace("/(([^a-zа-я][0-9]{1,3})[ \-]([а-яa-z] ?([^а-яa-z0-9 ]|$)))/iu", '$2$3 ', $str);
        
        // Форматиране на препинателни занци
        $str = trim(str_replace(array('.', ',', ';', ':', ' ,', ',,', '  ', '  ', ', ,', 'ж. к.', 'п. код '), array('. ', ', ', ', ', ', ', ',', ',', ' ', ' ', ', ', 'ж.к.', 'п.код '), $str), ' ,;.');
        
        
        $str = str_replace(array('В/у', ' И ', ' Вец ', ' Проф. ', ' Инж. ', ' Ген. ', ' Д-Р ', ' М-Р ', ' Д-Р. ', ' М-Р. ', ' Зпз ', ' Iii ', ' Ii ', ' Ндк ', ' Бпс '), array('в/у', ' и ', ' ВЕЦ ', ' проф. ', ' инж. ', ' ген. ', ' д-р ', ' м-р ', ' д-р ',' м-р ', ' Западна Промишлена Зона ', ' III ', ' II ', ' НДК ', ' Бизнес Парк София'), $str);
        
        $str = preg_replace('/, ул. ([0-9]{2,4}) ([0-9]{1,2})/iu', ', ул. "$1" №$2', $str);
        
        $arr = explode(',', $str);
        
        $parts = array();
        
        foreach ($arr as $i => $part) {
            while (strlen($part)) {
                $exPart = $part;
                $part = self::eatPart($part, $parts, $dict);
                if ($part === $exPart) {
                    $parts[] = $part;
                    break;
                }
            }
        }
        
        $all = $place = $addr = '';
        
        foreach ($parts as $k => &$v) {
            $v = trim($v, ' ,.-');
            if (is_numeric($k)) {
                $p = ', ' . $v;
                unset($parts[$k]);
            } else {
                $p = ', ' . $k . ' ' . $v;
            }
            
            $all .= $p;
            if ($k === 'c.' || $k === 'гр.' || $k === 'общ.' || $k === 'общ.') {
                $place .= $p;
            } elseif ($k !== 'п.код') {
                $addr .= $p;
            }
        }
        
        $parts['place'] = trim($place, ', ');
        $parts['addr'] = trim($addr, ', ');
        $parts['all'] = trim($all, ', ');
                
        return $parts;
    }
}
