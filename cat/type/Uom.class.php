<?php



/**
 * Клас  'cat_type_Uom' 
 * Тип за мерни еденици. Позволява да се въведе стойност с
 * нейната мярка по подобие на типа 'type_Time'. Примерно "5 килограма" и подобни.
 * Разпознава се коя мярка отговаря на посочения стринг и стойността се записва в
 * базата данни с основната си мярка
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_type_Uom extends type_Varchar {
    
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'double';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = '11';
    
    
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = 0;
    

    /**
     * Атрибути на елемента "<TD>" когато в него се записва стойност от този тип
     */
    public $cellAttr = 'align="right"';
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($val)
    {
        $val = trim($val);
        
        // Трябва да има зададена дефолт мерна еденица по нейно sysId
        expect($this->params['unit']);
        $typeUomId = cat_UoM::fetchBySysId($this->params['unit'])->id;
        
        // Празна стойност се приема за NULL
        if($val === '') return NULL;
        
        if(is_numeric($val)) {
        	
        	// Ако е въведено само число то се конвертира в основната мярка на мерната еденица
        	$val = cat_UoM::convertToBaseUnit($val, $typeUomId);
           
            return round($val);
        }
      
        // Разделяме текста на число и име
        preg_match("/(^[0-9 \.\,]+)([a-zа-я]*[\. ]*[a-zа-я]*)/umi", $val, $matches);
        
        // Първата намерена стойност е сумата на мярката
        $val = $matches[1];
        
        // Намерения текс се обръща във вид лесен за работа
        $ext = strtolower(str::utf2ascii($matches[2]));
        
        // Разпознава се на коя мерна еденица отговаря посочената дума
        $inputUom = cat_UoM::fetchBySinonim($ext);
        
        $Double = cls::get('type_Double');
        $val = $Double->fromVerbal($val);
        
    	if(!$val) {
            $this->error = "Недопустими символи в число/израз";
           
            return FALSE;
        }
        
        if(empty($inputUom)){
        	
        	// Задължително трябва да има разпознаване
        	$this->error = "Неразпозната мярка|* '{$matches[2]}'";
            
            return FALSE;
        }
        
        // Извличат се производните мерки на дефолт мярката
        $sameMeasures = cat_UoM::getSameTypeMeasures($typeUomId);
        if(empty($sameMeasures[$inputUom->id])){
        	
        	// Разпознатата мярка трябва да е от същия вид като дефолт мярката
        	// Така ако е зададено 'kg' неможе да се въведе примерно 'секунда'
        	$this->error = "Моля посочете мярка производна на|* '{$this->params['unit']}'";
           
            return FALSE;
        }
        
        // Въведената стойност се конвертира във основната си мярка
        $val = cat_UoM::convertToBaseUnit($val, $inputUom->id);
        
        // Връщане на стойността
        return $val;
    }
    
    
	/**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = '', &$attr = array())
    {
    	if($value && empty($this->error)){
    		$value = $this->toVerbal_($value);
    	}
        
        return ht::createTextInput($name, $value, $attr);
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal_($val)
    {
        if(!isset($val) || !is_numeric($val)) return NULL;
        $val = abs($val);
        
        return cat_UoM::smartConvert($val, $this->params['unit']);
    }
}