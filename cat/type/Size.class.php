<?php



/**
 * Клас  'cat_type_Size' 
 * Тип за размер (ширина, височина, дължина, дълбочина и пр.)
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
class cat_type_Size extends cat_type_Uom {
    
    
	/**
	 * Параметър по подразбиране
	 */
	function init($params = array())
    {
    	// Основната мярка на типа е метри
    	$this->params['unit'] = ($params['params']['unit']) ? $params['params']['unit'] : 'm';
    	$this->params = array_merge($this->params, $params['params']);
    	
    	parent::init($this->params);
    }
}