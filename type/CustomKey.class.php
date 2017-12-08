<?php


/**
 * Клас  'type_CustomKey' - Поле за външен ключ към произволен уникален ключ на друг модел
 *
 * Този клас е обобщение на класа type_Key. Също като type_Key, той представлява поле-външен 
 * ключ към друг модел. Разликата е, че докато при type_Key стойностите съответстват на 
 * първичния ключ на другия модел, при type_CustomKey стойностите съответстват на произволен
 * уникален ключ на другия модел. Името на този уникален ключ се задава с параметъра на типа
 * `key`.
 * 
 * Пример:
 * 
 * <code>
 *     $this->FLD('field', 'customKey(mvc=OtherModel, key=other_model_key_field, select=title)
 * </code>
 * 
 * В този пример полето `field` е външен ключ към модела `OtherModel` по неговото поле
 * `other_model_key_field` 
 * 
 * @TODO По идея тази фукционалност трябва да се премести в самия type_Key. Поради комплексността
 * на промените обаче, приемаме по-консервативен подход - клонираме класа type_Key, разработваме
 * и тестваме type_CustomKey и след това интегрираме промените обратно в type_Key. 
 *  
 *
 * @category  ef
 * @package   type
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_CustomKey extends type_Key 
{
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'varchar';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = '32';


    /**
     * Стойност по подразбиране
     */
    var $defaultValue = '';
    
    
    /**
     * 
     * 
     * @param string $value
     * 
     * @return object
     */
    protected function fetchVal(&$value)
    {
        $rec = $this->fetchForeignRec($value);
        
        return $rec;
    }
    
    
    /**
     * 
     * 
     * @param mixed $key
     * 
     * @return mixed
     */
    public function prepareKey($key)
    {
        // Всичко стойности са допустими
        
        return $key;
    }
    
    
    /**
     * 
     * @param mixed $keyValue
     * @return stdClass
     */
     protected function fetchForeignRec($keyValue)
     {
        $foreignModel = cls::get($this->params['mvc']);
        $keyField     = $this->getKeyField();
        
        $res = $foreignModel->fetch(array("#{$keyField} = '[#1#]'", $keyValue));
        
        // Ако няма стойност и стойноста е числова и полето на външния ключ не е инт, то се приема че в поелто
        // е записано ид-то на записа. Това може да стане ако полето от key е било сменено на customKey и не е
        // направена миграция, на даните
        if(!$res && is_numeric($keyValue) && !($foreignModel->getFieldType($keyField) instanceof type_Int)){
        	$res = $foreignModel->fetch((int)$keyValue);
        }
        
        return $res;
     }
    
    
    /**
     * Връща атрибутите на MySQL полето
     */
    public function getMysqlAttr()
    {
        // Извикваме базовата имплементация (дефинирана в core_Type), за да прескочим 
        // имплементацията на type_Int
        return $this->_baseGetMysqlAttr();
    }
}
