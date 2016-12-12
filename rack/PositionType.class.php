<?php



/**
 * Клас  'rack_PositionType' - Тип за позиция в складовото пространство
 *
 *
 * @category  bgerp
 * @package   rack
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class rack_PositionType extends type_Varchar {
    
	
	/**
	 * Параметър определящ максималната широчина на полето
	 */
	var $maxFieldSize = 9;
    

    /**
     * Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 9;

	
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    var $tdClass = 'centerCol';
    
        
    /**
     * Този метод трябва да конвертира от вербално към вътрешно представяне дадената стойност
     */
    function fromVerbal($value)
    {
        if(!trim($value)) return NULL;
        
        $matches = array();

        preg_match("/([0-9]{1,3})[\\-]{0,1}([a-z])[\\-]{0,1}([0-9]{1,3})/i", $value, $matches);

        if(!is_array($matches) || count($matches) != 4) {
            $this->error = "Невалиден синтаксис";

            return FALSE;
        }
        
        return strtoupper(((int)$matches[1]) . '-' . $matches[2] . '-' . ((int) $matches[3]));
    }
    
    /**
     * @todo Чака за документация...
     */
    function defVal()
    {
        return '';
    }
}