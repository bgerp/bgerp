<?php



/**
 * Клас  за композитен тип (варчар и дата) 'batch_type_VarcharAndDate' 
 * 
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class batch_type_VarcharAndDate extends type_Varchar {
    
	
	
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'varchar';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = '256';
    
    
    /**
     * @type_Varchar
     */
    protected $Varchar;
    
    
	/**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
    	$this->Varchar = cls::get('type_Varchar', $params);
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($value)
    {
    	// Ако няма стойност
    	if(!$value) return NULL;
    	
    	if(empty($value['lP']) || empty($value['rP'])){
    		$this->error = "Двете полета трябва да са въведени";
    		return FALSE;
    	}
    	
    	$valueString = $this->Varchar->fromVerbal($value['lP']);
    	if($value === FALSE){
    		$this->error = "Стринг";
    		return FALSE;
    	} else {
    		$length = $this->Varchar->params['length'];
    		if(mb_strlen($valueString) != $length){
    			$this->error = "Низа трябва да е точно|* {$length} |символа|*";
    			return FALSE;
    		}
    	}
    	
    	$valueDate = $this->Varchar->fromVerbal($value['rP']);
    	if($value === FALSE){
    		$this->error = "Невалиден низ";
    		return FALSE;
    	} else {
    		if($mask = $this->Varchar->params['requireMask']){
    			if(!dt::checkByMask($valueDate, $mask)){
    				$this->error = "Формата на датата трябва да е|* '{$mask}'";
    				return FALSE;
    			}
    		}
    	}
    	
    	$res = "{$valueString}/{$valueDate}";
    	
    	return $res;
    }
    
    
    /**
     * Рендиране на полето
     */
    function renderInput_($name, $value = '', &$attr = array())
	{
		// Ако има запис, конвертира се в удобен вид
		if(is_array($value)){
			$left = $value['lP'];
			$right = $value['rP'];
		} else {
			list($left, $right) = explode('/', $value);
		}
       
		if(empty($left) && isset($this->Varchar->params['prefix'])){
			$left = $this->Varchar->params['prefix'];
		}
		
        $inputLeft = $this->Varchar->renderInput($name . '[lP]', $left, $attr);
        unset($attr['placeholder']);
        
        if(isset($this->Varchar->params['requireMask'])){
        	$attr['placeholder'] = $this->Varchar->params['requireMask'];
        }
        
        $inputRight = " &nbsp;" . $this->Varchar->renderInput($name . '[rP]', $right, $attr);
        $inputRight = "<span style='vertical-align: top'>" . $inputRight . "</span>";
        $inputLeft->append($inputRight);
        
        // Връщане на готовото поле
        return $inputLeft;
	}
	
	
	/**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal_($value)
    {
    	return $this->Varchar->toVerbal($value);
    }
}