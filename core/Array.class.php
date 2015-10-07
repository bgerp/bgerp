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
    static function make($mixed, $noIntKeys = FALSE, $sep = NULL)
    {
        if (!$mixed) {
            return array();
        } elseif (is_array($mixed)) {
            $p = $mixed;
        } elseif (is_object($mixed)) {
            $p = get_object_vars($mixed);
        } elseif (is_scalar($mixed)) {
            
            if(!$sep) {
                $sep = substr($mixed, 0, 1);
                if (strlen($mixed) > 3 && $sep == substr($mixed, -1) && ($sep == ',' || $sep == '|')) {
                    $mixed = trim($mixed, $sep);
                } else {
                    $sep = ',';
                }
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
     * Сортира масив от обекти или от масиви по тяхното поле 'order'
     */
    static function order(&$array, $field = 'order', $mode = 'ASC')
    {
        if($mode == 'ASC') {
            usort($array, function($a, $b) use ($field) {
            		$a = (object)$a;
            		$b = (object)$b;
            		
                    if($a->{$field} == $b->{$field})  return 0;

                    return $a->{$field} > $b->{$field} ? 1 : -1;
                });
        } else {
            usort($array, function($a, $b) use ($field) {
	            	$a = (object)$a;
	            	$b = (object)$b;
            	
                    if($a->{$field} == $b->{$field})  return 0;

                    return $a->{$field} > $b->{$field} ? -1 : 1;
                });

        }
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
    
    
    /**
     * Преобразува от масив с индекси името и стойност вербалното представане към enum
     * 
     * @param array $arr - Масив с данни
     * 
     * @return string
     */
    static function fromArray($arr)
    {
        $resStr = '';
        
        // Обхождаме масива
        foreach ((array)$arr as $name => $verbalName) {
            
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
     * @param string $fields
     *
     * @return array|object
     */
    public static function copy($arr1, $arr2, $fields = NULL)
    {
        if(is_object($arr2)) {
            $vars = get_object_vars($arr2);
        } else {
            $vars = $arr2;
        }
        if($fields === NULL) {
            $fields = array_keys($vars);
        } else {
            $fields = arr::make($fields);
        }

        foreach($fields as $fld) {
            if(is_object($arr1)) {
                $arr1->{$fld} = $vars[$fld];
            } elseif(is_array($arr1)) {
                $arr1[$fld] = $vars[$fld];
            } else {
                // Некоректен параметър
                bp($arr1);
            }
        }
    }
    
    
    /**
     * Поставя елементите на масив на преди или след елементите на друг масив
     * 
     * @param array $array - асоциативния масив в който ще слагаме елементите
     * @param mixed $elementArr - масив с елементи или стринг
     * @param string $before - преди кой ключ да се сложи
     * @param string $after - след кой ключ да се сложи
     */
    public static function placeInAssocArray(&$array, $elementArr, $before = NULL, $after = NULL)
    {
    	expect(is_array($array));
    	$elementsArr = arr::make($elementArr, TRUE);
    	
    	if(isset($before) || isset($after)) {
    		foreach ($elementsArr as $key => $value){
    			$newFields = array();
    			
    			$isSet = FALSE;
    			 
    			foreach($array as $exName => $exFld) {
    			
    				if($before == $exName) {
    					$isSet = TRUE;
    					$newFields[$key] = $value;
    				}
    			
    				if(!$isSet || ($exName != $key)) {
    					$newFields[$exName] = &$array[$exName];
    				}
    			
    				if($after == $exName) {
    					$newFields[$key] = $value;
    					$isSet = TRUE;
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
     * @param array $new - масив с нови данни
     * @param array $old - масив със съществуващи данни
     * @param mixed $keyFields - Уникални полета
     * @param mixed $valueFields - стойностти които ще сравняваме
     * 
     * @return array ['update'] - масив със стойностти за обновяване
     * 				   ['insert'] - масив със стойностти за добавяне
     * 				   ['delete'] - записи от съществуващите, несрещащи се в $new
     */
    public static function syncArrays($new, $old, $keyFields, $valueFields)
    {
    	$modOld = $modNew = array();
    	$keyFields = arr::make($keyFields, TRUE);
    	$vFields = arr::make($valueFields, TRUE);
    	
    	// Нормализираме масива със същ. данни във вид лесен за обработка
    	if(count($old)){
    		foreach ($old as $oRec){
    			$oKey = self::makeUniqueIndex($oRec, $keyFields);
    			$vKey = self::makeUniqueIndex($oRec, $vFields);
    		
    			// Преобразуваме го в масив в индекси уникалните полета и информация за данните му
    			if(!array_key_exists($oKey, $modOld)){
    				$modOld[$oKey] = array($vKey, $oRec->id);
    			}
    		}
    	}
    	
    	$insert = $upArr = array();
    	
    	// Обикаляме масива с нови данни
    	if(count($new)){
    		foreach ($new as $nRec){
    			$nKey = self::makeUniqueIndex($nRec, $keyFields);
    			$nValKey = self::makeUniqueIndex($nRec, $vFields);
    			
    			$uRec = clone $nRec;
    		
    			// Ако записа се среща с този индекс и с тази стойност на зададените полета в $modOld
    			// то отбелязваме записа, че е за обновяване
    			if(array_key_exists($nKey, $modOld)){
    				if($modOld[$nKey][0] != $nValKey){
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
    	if(count($modOld)){
    		foreach ($modOld as $ar){
    			$delete[$ar[1]] = $ar[1];
    		}
    	}
    	
    	// Връщаме масивите за обновяване и за изтриване
    	return array('insert' => $insert, 'update' => $upArr, 'delete' => $delete);
    }
    
    
    /**
     * Връща уникален индекс от полета в обект
     * 
     * @param stdClass $rec - запис
     * @param mixed $keyFields - списък с полета за уникалния индекс
     * @return string $nKey - уникалния индекс
     */
    public static function makeUniqueIndex($rec, $keyFields)
    {
    	$keyFields = arr::make($keyFields, TRUE);
    	$nKey = '';
    	
    	foreach ($keyFields as $key){
    		$nKey .= $rec->$key . "|";
    	}
    	
    	return $nKey;
    }
}