<?php


/**
 * Клас 'core_Array' ['arr'] - Функции за работа с масиви
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Array
{
    public static $rand;
    
    
    /**
     * Конкатенира към стойностите от първия масив, стойностите от втория със
     * същите ключове
     */
    public static function union($a1, $a2)
    {
        foreach ($a2 as $key => $value) {
            $a1[$key] .= $value;
        }
        
        return $a1;
    }
    
    
    /**
     * Събира няколко масива или списъка, като запазва ключовете им
     */
    public static function combine()
    {
        $res = array();
        
        $args = func_get_args();
        
        if (count($args)) {
            foreach ($args as $a) {
                if (!is_array($a)) {
                    $a = arr::make($a, true);
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
    public static function make($mixed, $noIntKeys = false, $sep = null)
    {
        if (!$mixed) {
            
            return array();
        } elseif (is_array($mixed)) {
            $p = $mixed;
        } elseif (is_object($mixed)) {
            $p = get_object_vars($mixed);
        } elseif (is_scalar($mixed)) {
            if (!$sep) {
                $sep = substr($mixed, 0, 1);
                if (strlen($mixed) > 3 && $sep == substr($mixed, -1) && ($sep == ',' || $sep == '|')) {
                    $mixed = trim($mixed, $sep);
                } else {
                    $sep = ',';
                }
            }
            
            
            /**
             * Ескейпваме двойния сепаратор
             *
             * @todo: Необходимо ли е?
             */
            if (!static::$rand) {
                static::$rand = '[' . rand(-2000000000, 2000000000) . rand(-2000000000, 2000000000) . ']';
            }
            $mixed = str_replace($sep . $sep, static::$rand, $mixed);
            
            $mixed = explode($sep, $mixed);
            $p = array();
            
            if (count($mixed) > 0) {
                foreach ($mixed as $index => $value) {
                    $value = str_replace(static::$rand, $sep, $value);
                    
                    if (strpos($value, '=') > 0) {
                        list($key, $val) = explode('=', $value);
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
    public static function haveSection($arr1, $arr2)
    {
        $arr1 = arr::make($arr1, true);
        $arr2 = arr::make($arr2, true);
        
        if ((count($arr1) == 0) || (count($arr2) == 0)) {
            
            return true;
        }
        
        foreach ($arr1 as $key => $value) {
            if (isset($arr2[$key])) {
                
                return true;
            }
        }
        
        foreach ($arr2 as $key => $value) {
            if (isset($arr1[$key])) {
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща ключа на елемента с най-голяма стойност
     */
    public static function getMaxValueKey($arr)
    {
        if (count($arr)) {
            
            return array_search(max($arr), $arr);
        }
    }
    
    
    /**
     * Сортира масив от обекти или от масиви по тяхното поле 'order'
     *
     * @param $array array   Масива, който ще се подрежда
     * @param $field string  Име на полето по което се подрежда
     * @param $dir   string  Посока на подредбата ('asc' или 'desc')
     * @param $mode  string  Типа на сравнението
     *                о   'native'  - така, както се прави сравнение в PHP с <, >, и ==
     *                o   'str'     - стрингово сравнение
     *                о   'stri'    - стрингово сравнение без отчитане на кейса
     *                о   'natural' - стрингово сравнение, използвайки natural sorting algorityma
     *
     * @return void
     */
    public static function sortObjects(&$array, $field = 'order', $dir = 'asc', $mode = 'native')
    {
        $mode = strtolower($mode);
        $dir = strtolower($dir);
        expect($dir == 'desc' || $dir == 'asc', $dir);
        
        uasort($array, function ($a, $b) use ($field, $dir, $mode) {
            $a = (object) $a;
            expect(property_exists($a, $field), $a, $field);
            
            $b = (object) $b;
            expect(property_exists($b, $field), $b, $field);
            
            if ($mode == 'native') {
                if ($a->{$field} == $b->{$field}) {
                    $res = 0;
                } else {
                    $res = ($dir == 'asc' ? 1 : -1) * ($a->{$field} > $b->{$field} ? 1 : -1);
                }
            } elseif ($mode == 'str') {
                $res = ($dir == 'asc' ? 1 : -1) * strcmp($a->{$field}, $b->{$field});
            } elseif ($mode == 'stri') {
                $res = ($dir == 'asc' ? 1 : -1) * strcasecmp($a->{$field}, $b->{$field});
            } elseif ($mode == 'natural') {
                $res = ($dir == 'asc' ? 1 : -1) * strnatcasecmp($a->{$field}, $b->{$field});
            } else {
                expect(in_array($mode, array('native', 'str', 'stri')), $mode);
            }
            
            return $res;
        });
    }
    
    
    /**
     * Групира масив от записи (масиви или обекти) по зададено поле-признак
     *
     * @param array  $data  масив от асоциативни масиви и/или обекти
     * @param string $field
     *
     * @return array
     */
    public static function group($data, $field)
    {
        $result = array();
        
        foreach ($data as $i => $r) {
            $key = is_object($r) ? $r->{$field} : $r[$field];
            $result[$key][$i] = $r;
        }
        
        return $result;
    }
    
    
    /**
     * Превръща многомерен масив в стринг
     *
     * @param array  $array     - Многомерен масив, който ще извличаме
     * @param string $field     - Полето, което ще извличаме. Ако не е зададено, извлича всички елементи
     * @param string $delimiter - Разделителя между елементите на масива
     *
     * @return string $str - Стринга, който ще връщаме
     */
    public static function extractMultidimensionArray($array, $field = false, $delimiter = ', ')
    {
        // Стринга, който ще връщаме
        $str = '';
        
        // Ако има елементи в масива
        if (count($array)) {
            
            // Ако е зададено полето
            if ($field !== false) {
                
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
    
    
    /**
     * Преобразува от масив с индекси името и стойност вербалното представане към enum
     *
     * @param array $arr - Масив с данни
     *
     * @return string
     */
    public static function fromArray($arr)
    {
        $resStr = '';
        
        // Обхождаме масива
        foreach ((array) $arr as $name => $verbalName) {
            
            // Генерираме стринг
            $resStr .= ($resStr) ? ',' : '';
            $resStr .= $name . '=' . $verbalName;
        }
        
        return $resStr;
    }
    
    
    /**
     * Копиране на свойства от един обект/масив на друг
     *
     * @param array|object $arr1
     * @param array|object $arr1
     * @param string       $fields
     *
     * @return array|object
     */
    public static function copy($arr1, $arr2, $fields = null)
    {
        if (is_object($arr2)) {
            $vars = get_object_vars($arr2);
        } else {
            $vars = $arr2;
        }
        if ($fields === null) {
            $fields = array_keys($vars);
        } else {
            $fields = arr::make($fields);
        }
        
        foreach ($fields as $fld) {
            if (is_object($arr1)) {
                $arr1->{$fld} = $vars[$fld];
            } elseif (is_array($arr1)) {
                $arr1[$fld] = $vars[$fld];
            } else {
                // Некоректен параметър
                error('Некоректен параметър', $arr1);
            }
        }
        
        return $arr1;
    }
    
    
    /**
     * Поставя елементите на масив на преди или след елементите на друг масив
     *
     * @param array  $array      - асоциативния масив в който ще слагаме елементите
     * @param mixed  $elementArr - масив с елементи или стринг
     * @param string $before     - преди кой ключ да се сложи
     * @param string $after      - след кой ключ да се сложи
     */
    public static function placeInAssocArray(&$array, $elementArr, $before = null, $after = null)
    {
        expect(is_array($array));
        $elementsArr = arr::make($elementArr, true);
        
        if (isset($before) || isset($after)) {
            foreach ($elementsArr as $key => $value) {
                $newFields = array();
                
                $isSet = false;
                
                foreach ($array as $exName => $exFld) {
                    if ((string) $before == (string) $exName) {
                        $isSet = true;
                        $newFields[$key] = $value;
                    }
                    
                    if (!$isSet || ($exName != $key)) {
                        $newFields[$exName] = &$array[$exName];
                    }
                    
                    if ((string) $after == (string) $exName) {
                        $newFields[$key] = $value;
                        $isSet = true;
                    }
                }
                
                $array = $newFields;
            }
        } else {
            $array = array_merge($array, $elementsArr);
        }
    }
    
    
    /**
     * Ф-я за синхронизиране на масиви от обекти
     *
     * @param array $new         - масив с нови данни
     * @param array $old         - масив със съществуващи данни
     * @param mixed $keyFields   - Уникални полета
     * @param mixed $valueFields - стойностти които ще сравняваме
     *
     * @return array ['update'] - масив със стойностти за обновяване
     *               ['insert'] - масив със стойностти за добавяне
     *               ['delete'] - записи от съществуващите, несрещащи се в $new
     */
    public static function syncArrays($new, $old, $keyFields, $valueFields)
    {
        $modOld = $modNew = array();
        $keyFields = arr::make($keyFields, true);
        $vFields = arr::make($valueFields, true);
        
        // Нормализираме масива със същ. данни във вид лесен за обработка
        if (is_array($old)) {
            foreach ($old as $oRec) {
                $oKey = self::makeUniqueIndex($oRec, $keyFields);
                $vKey = self::makeUniqueIndex($oRec, $vFields);
                
                // Преобразуваме го в масив в индекси уникалните полета и информация за данните му
                if (!array_key_exists($oKey, $modOld)) {
                    $modOld[$oKey] = array($vKey, $oRec->id);
                }
            }
        }
        
        $insert = $upArr = array();
        
        // Обикаляме масива с нови данни
        if (is_array($new)) {
            foreach ($new as $nRec) {
                $nKey = self::makeUniqueIndex($nRec, $keyFields);
                $nValKey = self::makeUniqueIndex($nRec, $vFields);
                
                $uRec = clone $nRec;
                
                // Ако записа се среща с този индекс и с тази стойност на зададените полета в $modOld
                // то отбелязваме записа, че е за обновяване
                if (array_key_exists($nKey, $modOld)) {
                    if ($modOld[$nKey][0] != $nValKey) {
                        $uRec->id = $modOld[$nKey][1];
                        $upArr[] = $uRec;
                    }
                } else {
                    $insert[] = $uRec;
                }
                
                // Премахваме записа от стария масив
                unset($modOld[$nKey]);
            }
        }
        
        // Обръщаме останалите елементи в масив само с ид-та
        $delete = array();
        if (is_array($modOld)) {
            foreach ($modOld as $ar) {
                $delete[$ar[1]] = $ar[1];
            }
        }
        
        // Връщаме масивите за обновяване и за изтриване
        return array('insert' => $insert, 'update' => $upArr, 'delete' => $delete);
    }
    
    
    /**
     * Връща уникален индекс от полета в обект
     *
     * @param stdClass $rec       - запис
     * @param mixed    $keyFields - списък с полета за уникалния индекс
     *
     * @return string $nKey - уникалния индекс
     */
    public static function makeUniqueIndex($rec, $keyFields)
    {
        $keyFields = arr::make($keyFields, true);
        $nKey = '';
        
        foreach ($keyFields as $key) {
            $nKey .= $rec->$key . '|';
        }
        
        return $nKey;
    }
    
    
    /**
     * Връща броя на елементите в масива
     * Ако аргумента не е масив - предполага, че се каства към FALSE
     */
    public static function count($arr)
    {
        if (is_array($arr)) {
            
            return count($arr);
        }
        
        // Очаква се или масив или == FALSE
        expect(!$arr, $arr);
    }
    
    
    /**
     * Допълва в един масив ключовете, които липсват в него
     *
     * @param stdClass|array $objectToFill   - масив или запис, който ще се допълва
     * @param stdClass|array $fillFromObject - масив или запис, от който ще се допълват
     *
     * @return array $arrayToFill - оригиналния масив или запис, но с допълнени стойности
     */
    public static function fillMissingKeys($objectToFill, $fillFromObject)
    {
        // Подсигуряваме се, че работим с масиви
        $arrayToFill = (array) $objectToFill;
        $arraySource = (array) $fillFromObject;
        
        // Обхождаме източника, и ако няма такъв ключ в подадения масив, се добавя
        foreach ($arraySource as $key => $value) {
            if (!array_key_exists($key, $arrayToFill)) {
                $arrayToFill[$key] = $value;
            }
        }
        
        // Връщаме допълнения масив
        return $arrayToFill;
    }
    
    
    /**
     * Извлича масив със стойностите на определено поле от масив от обекти/масиви
     *
     * @param array  $arr   - масив от който ще се извличат стойностите
     * @param string $field - стойност на записа за екстрактване
     *
     * @return array $result - екстракнатите стойности, в масив
     */
    public static function extractValuesFromArray($arr, $field)
    {
        expect(is_array($arr));
        $result = array_values(array_map(function ($obj) use ($field) {
            
            return (is_object($obj)) ? $obj->{$field} : $obj[$field];
        }, $arr));
        $result = array_values($result);
        if (count($result)) {
            $result = array_combine($result, $result);
        }
        
        return $result;
    }
    
    
    /**
     * Извлича масив със стойности от масив със други стойности
     *
     * @param array  $arr    - масив от който ще се извличат стойностите
     * @param string $fields - полета
     *
     * @return array $res     - екстракнатите стойности, в масив
     */
    public static function extractSubArray($arr, $fields)
    {
        $fields = arr::make($fields, true);
        expect(count($fields));
        $res = array_values(array_map(function ($obj) use ($fields) {
            $res = new stdClass();
            foreach ($fields as $fld) {
                $res->{$fld} = is_object($obj) ? $obj->{$fld} : $obj[$fld];
            }
            
            return $res;
        }, $arr));
        
        return $res;
    }
    
    
    /**
     * Ф-я проверяваща дали два масива/обекта имат еднакви ключове/стойности, без да е нужно да са в
     * същата последователност
     *
     * @param array|stdClass $array1
     * @param array|stdClass $array2
     *
     * @return bool $res
     */
    public static function areEqual($array1, $array2)
    {
        $a = (array) $array1;
        $b = (array) $array2;
        
        $res = (is_array($a) && is_array($b) && count($a) == count($b) && array_diff($a, $b) === array_diff($b, $a));
        
        return $res;
    }
    
    
    /**
     * Вмъкване на подмасив в масив
     *
     * @param array      $array    Оригинален масив
     * @param int|string $position Стрингов индекс или числова позиция
     * @param mixed      $insert   Какво ще вмъкваме
     * @param bool       $after    След или преди позицията
     */
    public static function insert(&$array, $position, $insert, $after = false)
    {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos = array_search($position, array_keys($array)) + $after;
            $array = array_merge(
                array_slice($array, 0, $pos),
                $insert,
                array_slice($array, $pos)
            );
        }
    }
    
    
    /**
     * Връща нов масив само с посочените стойности от подадения масив
     *
     * @param stdClass|array $arr    - обект
     * @param string|array   $fields - кои стойностти да се върнат
     *
     * @return array $resObj    - резултатен обект
     */
    public static function getSubArray($arr, $fields)
    {
        $res = array();
        $arr1 = (array) $arr;
        
        $fields = arr::make($fields, true);
        if (is_array($fields)) {
            foreach ($fields as $fld) {
                $res[$fld] = $arr1[$fld];
            }
        }
        
        return $res;
    }
    
    
    /**
     * Сумира стойноста на всички полета от масив от обекти/масиви
     *
     * @param stdClass|array $arr         - обект
     * @param string|array   $field       - кое поле да се събере
     * @param boolean        $emptyAsZero - да третирали липсващото поле като нула
     *
     * @return null|float $sum - сумата от полетата или null за липсата на такава
     */
    public static function sumValuesArray($arr, $field, $emptyAsZero = false)
    {
        $sum = null;
        $arr = arr::make($arr);
        
        array_walk($arr, function ($a) use (&$sum, $field, $emptyAsZero) {
            $value = is_array($a) ? $a[$field] : $a->{$field};
            
            if($emptyAsZero === false){
                expect(isset($value), "Няма поле: {$field}");
            }
            
            $sum += $value;
        });
        
        return $sum;
    }
    
    
    /**
     * Проверява дали посочените стрингови елементи от масива имат минимална дължина
     * 
     * @param array $arr - масив
     * @param int $checkLength - минимална дължина за проверка
     * @param null|string $field - поле, което ще се проверява. При null приема че масива е от скалари
     * @return boolean $res - дали търсените елементи от масива имат минимална дължина
     */
    public static function checkMinLength($arr, $checkLength, $field = null)
    {
        $res = true;
        array_walk($arr, function ($a) use ($checkLength, $field, &$res){
            $val = $a;
            if(isset($field)){
                $val = (is_array($a)) ? $a[$field] : $a->{$field};
            }
            
            if(mb_strlen($val) < $checkLength) {
                $res = false;
            }
        });
        
        return (count($arr)) ? $res : false;
    }
}
