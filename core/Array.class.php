<?php



/**
 * Клас 'core_Array' ['arr'] - Функции за работа с масиви
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
class core_Array
{

    static $rand;

    /**
     * Конкатенира към стойностите от първия масив, стойностите от втория със
     * същите ключове
     */
    static function union($a1, $a2)
    {
        foreach ($a2 as $key => $value) {
            $a1[$key] .= $value;
        }

        return $a1;
    }


    /**
     * @todo Чака за документация...
     */
    static function combine()
    {
        $res = array();

        $args = func_get_args();

        if (count($args)) {
            foreach ($args as $a) {
                if(!is_array($a)) {
                    $a = arr::make($a, TRUE);
                }

                foreach ($a as $key => $value) {
                    if (!isset($res[$key])) {
                        $res[$key] = $value;
                    } elseif (is_array($res[$key]) && is_array($value)) {
                        $res[$key] = arr::combine($res[$key], $value);
                    }
                }
            }
        }

        return $res;
    }


    /**
     * Конвертира стрингов списък или обект, към масив
     * Може да не слага целочислени индекси, като наместо тях
     * слага за индекси самите стойности.
     * Използва се повсеместно във фреймуърка за предаване като параметри на масиви
     * които могат да се запишат във вида "a=23,b=ddd,c=ert->wer"
     * Само знаците '=' и ',' не могат да се използват в ключовете или стойностите
     */
    static function make($mixed, $noIntKeys = FALSE)
    {
        if (!$mixed) {
            return array();
        } elseif (is_array($mixed)) {
            $p = $mixed;
        } elseif (is_object($mixed)) {
            $p = get_object_vars($mixed);
        } elseif (is_string($mixed)) {
            $sep = substr($mixes, 0, 1);

            if (strlen($mixed) > 3 && $sep == substr($mixes, -1) && ($sep == ',' || $sep == '|')) {
                $mixed = trim($mixes, $sep);
            } else {
                $sep = ',';
            }

            /**
             * Ескейпваме двойния сепаратор
             * @todo: Необходимо ли е?
             */
            

            if (!static::$rand) {
                static::$rand = "[" . rand(-2000000000, 2000000000) . rand(-2000000000, 2000000000) . "]";
            }
            $mixed = str_replace($sep . $sep, static::$rand, $mixed);

            $mixed = explode($sep, $mixed);
            $p = array();

            if (count($mixed > 0)) {
                foreach ($mixed as $index => $value) {
                    $value = str_replace(static::$rand, $sep, $value);

                    if (strpos($value, "=") > 0) {
                        list($key, $val) = explode("=", $value);
                        $p[trim($key)] = trim($val);
                    } else {
                        $p[] = trim($value);
                    }
                }
            }
        }

        // Ако е необходимо, махаме числовите индекси
        if ($noIntKeys && count($p) > 0) {
            foreach ($p as $k => $v) {
                if (is_int($k)) {
                    $p1[$v] = $v;
                } else {
                    $p1[$k] = $v;
                }
            }
            $p = $p1;
        }

        return $p;
    }


    /**
     * Дали ключовете на двата масива имат сечение
     * Ако един от двата масива е празен, то резултата е истина
     * защото, често в EF празния масив означава всички допустими елементи
     */
    static function haveSection($arr1, $arr2)
    {
        $arr1 = arr::make($arr1, TRUE);
        $arr2 = arr::make($arr2, TRUE);

        if((count($arr1) == 0) || (count($arr2) == 0)) return TRUE;

        foreach($arr1 as $key => $value) {
            if(isset($arr2[$key])) return TRUE;
        }

        foreach($arr2 as $key => $value) {
            if(isset($arr1[$key])) return TRUE;
        }

        return FALSE;
    }


    /**
     * Връща ключа на елемента с най-голяма стойност
     */
    static function getMaxValueKey($arr)
    {
        if(count($arr)) {
            return array_search(max($arr), $arr);
        }
    }


    /**
     * Сортира масив от обекти по тяхното поле 'order'
     */
    static function order(&$array, $field = 'order')
    {
        usort($array, function($a, $b) use ($field) {
                if($a->{$field} == $b->{$field})  return 0;

                return $a->{$field} > $b->{$field} ? 1 : -1;
            });
    }
    
    
	/**
     * Сортира масив от обекти по тяхното поле 'order' и запазва ключа
     */
    static function orderA(&$array, $field = 'order')
    {
        uasort($array, function($a, $b) use ($field) {
            
            // Ако липсва да се подредят най накрая
//            if (!isset($a->$field)) return 1;
//            if (!isset($a->$field)) return -1;
            
//            if($a->{$field} == $b->{$field})  return 0;
            // Ако има 2 елемента с еднакви стойности, първия срещнат да си остане първи
            if($a->{$field} == $b->{$field})  return 1;

            return $a->{$field} > $b->{$field} ? 1 : -1;
        });
    }


    /**
     * Групира масив от записи (масиви или обекти) по зададено поле-признак
     *
     * @param array $data масив от асоциативни масиви и/или обекти
     * @param string $field
     * @return array
     */
    static function group($data, $field)
    {
        $result = array();

        foreach ($data as $i=>$r) {
            $key = is_object($r) ? $r->{$field} : $r[$field];
            $result[$key][$i] = $r;
        }

        return $result;
    }
    
    
	/**
     * Превръща многомерен масив в стринг
     * 
     * @param array $array - Многомерен масив, който ще извличаме
     * @param string $field - Полето, което ще извличаме. Ако не е зададено, извлича всички елементи
     * @param string $delimiter - Разделителя между елементите на масива
     * 
     * @return string $str - Стринга, който ще връщаме
     */
    function extractMultidimensionArray($array, $field=FALSE, $delimiter=', ') 
    { 
        // Стринга, който ще връщаме
        $str = '';
        
        // Ако има елементи в масива
        if (count($array)) {
            
            // Ако е зададено полето
            if ($field !== FALSE) {
                
                // Ако има елементи в подмасива със съответните елементи
                if (count($array[$field])) {
                    
                    // Обхождаме подмасива
                    foreach ($array[$field] as $key => $value) {
                        
                        // Ако все още е масив
                        if (is_array($value)) {
                            
                            // Извикаваме функцията рекурсивно
                            $strRecurs = self::extractMultidimensionArray($array[$field], $key);
                            
                            // Получения резултат го добавяме към стринга
                            $str .= ($str) ? $delimiter . $strRecurs : $strRecurs;    
                        } else {
                            
                            // Ако е стринг, добавяме го към стринга
                            $str .= ($str) ? $delimiter . $value : $value;    
                        }
                    }
                }    
            } else {
                
                // Обхождаме масива
                foreach ($array as $key => $value) {
                    
                    // Ако има елементи в подмасива със съответните елементи
                    if (count($array[$key])) {
                        
                        // Обхождаме подмасива
                        foreach ($array[$key] as $keyV => $val) {
                            
                            // Ако все още е масив
                            if (is_array($val)) {
                                
                                // Извикаваме функцията рекурсивно
                                $strRecurs = self::extractMultidimensionArray($array[$key], $keyV);
                                
                                // Получения резултат го добавяме към стринга
                                $str .= ($str) ? $delimiter . $strRecurs : $strRecurs;
                            } else {
                                
                                // Ако е стринг, добавяме го към стринга
                                $str .= ($str) ? $delimiter . $val : $val;
                            }
                        }    
                    }
                }
            }
        }
        
        return $str;
    }
}