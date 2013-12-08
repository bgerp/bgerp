<?php



/**
 * Клас  'cat_type_Size' 
 * Тип за Размер, приема стойности от рода "5 м" и ги конвертира до основната еденица
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
class cat_type_Size extends cat_type_Uom {
    
    
	/**
	 * Параметър по подразбиране
	 */
	function init($params = array())
    {
    	$this->params['unit'] = 'm';
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($val)
    {
    	$this->params['unit'] = 'm';
    	
    	$val = parent::fromVerbal_($val);
    	
    	if($val === FALSE){
    		$this->error = "Моля въведете валидна мярка за размер";
            
            return FALSE;
    	}
    	
    	return $val;
    }
}