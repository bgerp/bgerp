<?php

/**
 * Клас 'core_Array' ['arr'] - Функции за работа с масиви
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Array
{
    
    
    /**
     * Конкатинира към стойностите от първия масив, стойностите от втория със
     * същите клюючове
     */
    function union($a1, $a2)
    {
        foreach ($a2 as $key => $value) {
            $a1[$key] .= $value;
        }
        
        return $a1;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function combine()
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
     * Конвертира стрингов смисък или обект, към масив
     * Може да не слага целочислени индекси, като наместо тях
     * слага за индекси самите стойности.
     * Използва се повсеместно във фреймуърка за предаване като параметри на масиви
     * които могат да се запишат във вида "a=23,b=ddd,c=ert->wer"
     * Само знаците '=' и ',' не могат да се използват в ключовете или стойностите
     */
    function make($mixed, $noIntKeys = FALSE)
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
            static $rand;
            
            if (!$rand) {
                $rand = "[" . rand(-2000000000, 2000000000) . rand(-2000000000, 2000000000) . "]";
            }
            $mixed = str_replace($sep . $sep, $rand, $mixed);
            
            $mixed = explode($sep, $mixed);
            $p = array();
            
            if (count($mixed > 0)) {
                foreach ($mixed as $index => $value) {
                    $value = str_replace($rand, $sep, $value);
                    
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
    function haveSection($arr1, $arr2)
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
}