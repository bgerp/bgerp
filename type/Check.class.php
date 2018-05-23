<?php



/**
 * Клас  'type_Check' - Тип за избрана/неизбрана стойност
 *
 *
 * @category  bgerp
 * @package   type
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Check extends type_Enum {
	
	
	/**
	 * Параметър по подразбиране
	 */
	function init($params = array())
	{
		$yesCaption = isset($params['params']['value']) ? $params['params']['value'] : 'Да';
		$this->options = array('no' => 'Не е направен избор', 'yes' => $yesCaption);
		
		parent::init($this->params);
	}
	
	
	/**
	 * Рендира HTML инпут поле
	 */
	function renderInput_($name, $value = "", &$attr = array())
	{
		$caption = $this->options['yes'];
		$tpl = "<input type='checkbox' name='{$name}' value='yes' class='checkbox'" . ($value == 'yes' ? ' checked ' : '') . "> {$caption}";
		
		return $tpl;
	}
	
	
	/**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
    	$value = ($value == 'yes') ? 'yes' : 'no';
    	
    	if(isset($this->params['mandatory']) && $value != 'yes'){
    		$this->error = "Стойността трябва да е избрана|*!";
    	}
    	
    	return $value;
    }
}