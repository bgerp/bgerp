<?php



/**
 * Клас  'cat_type_Volume' 
 * Тип за Тегло, приема стойности от рода "5 кг" и ги конвертира до основната еденица
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_type_Volume extends cat_type_Uom {
    
    
	/**
	 * Параметър по подразбиране
	 */
	function init($params = array())
    {
    	$this->params['unit'] = 'cub.m';
    	parent::init($this->params);
    }
}