<?php



/**
 * Клас  'cat_type_Weight' 
 * Тип за Тегло
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_type_Weight extends cat_type_Uom {
    
	
	/**
	 * Параметър по подразбиране
	 */
	function init($params = array())
    {
    	// Основната мярка на типа е килограм
    	$this->params['unit'] = 'kg';
    	if(is_array($params['params'])){
    		$this->params = array_merge($this->params, $params['params']);
    	}
    	
    	parent::init($this->params);
    }
}