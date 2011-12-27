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
     *
     */
    function init($params)
    {
        parent::init($params);
        $this->dt = cls::get('type_Date', $params);
    }
    
    /**
     * var $inputType   = 'datetime-local';
     */
    function renderInput_($name, $value="", $attr = array())
    {
        setIfNot($value, $attr['value']);

        if($value) {
            list($date, $time) = explode(' ', $this->toVerbal('2011-11-11 22:50'));
        } 
        
        $attr['size'] = 10;
        $attr['value'] = $date;
        $input = $this->dt->renderInput($name . '[d]', NULL, $attr);
        $attr['size'] = 5;
        $input->append('&nbsp;');
        $attr['value'] = $time;
        $input->append($this->createInput($name . '[t]', NULL, $attr));
        
        return $input;
    }


    /**
     *  @todo Чака за документация...
     */
    function fromVerbal($value)
    {
        if(!count($value)) return NULL;

        $value = dt::verbal2mysql(trim(trim($value['d']) . ' ' . trim($value['t'])));
        if($value) {
            
            return $value;
        } else {
            $now = $this->toVerbal(dt::verbal2mysql('', !empty($this->timePart)));
            $this->error = "Не е в допустимите формати, като например|*: '<B>{$now}</B>'";
            
            return FALSE;
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function defVal()
    {
        return date("Y-m-d h:i:s");
    }
}