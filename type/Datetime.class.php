<?php

/**
 * Формат по подразбиране за времевата част
 */
defIfNot('EF_DATETIME_TIME_PART', ' H:i');


/**
 * Клас  'type_Datetime' - Тип за време
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Datetime extends type_Date {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'datetime';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $timePart = EF_DATETIME_TIME_PART;
    
    
    /**
     * var $inputType   = 'datetime-local';
     */
    function renderInput_($name, $value="", $attr = array())
    {
        $attr['size'] = 20;
        
        if($value) {
            $value = $this->toVerbal($value);
        } else {
            $value = $attr['value'];
        }
        
        $input = $this->createInput($name, $value, $attr);
        
        return $input;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function defVal()
    {
        return date("Y-m-d h:i:s");
    }
}