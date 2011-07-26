<?php

/**
 * Формат по подразбиране за датите
 */
defIfNot('EF_DATE_FORMAT', 'd-m-YEAR');


/**
 * Формат по подразбиране за датата при тесни екрани
 */
defIfNot('EF_DATE_NARROW_FORMAT', 'd/m-year');


/**
 * Клас  'type_Date' - Тип за дати
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
class type_Date extends core_Type {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'date';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $cellAttr = 'align="center" nowrap';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $timePart = '';
    
    
    /**
     *  @todo Чака за документация...
     */
    function toVerbal($value, $time = '')
    {
        if(!$value) return NULL;
        
        if($this->param['format']) {
            $format = $this->param['format'];
        } elseif(Mode::is('screenMode', 'narrow')) {
            $format = EF_DATE_NARROW_FORMAT . $this->timePart;
        } else {
            $format = EF_DATE_FORMAT . $this->timePart;
        }
        $date = dt::mysql2verbal($value, $format);
        
        return $date;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function fromVerbal($value)
    {
        if(!trim($value)) return NULL;
        $value = dt::verbal2mysql($value);
        
        if($value) {
            
            return $value;
        } else {
            $now = $this->toVerbal(dt::verbal2mysql());
            $this->error = "Не е в допустимите формати, като например|*: '<B>{$now}</B>'";
            
            return FALSE;
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
    {
        $attr['name'] = $name;
        
        setIfNot($attr['size'], 20);
        
        if($value) {
            $value = $this->toVerbal($value);
        } else {
            $value = $attr['value'];
        }
        
        return $this->createInput($name, $value, $attr);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function defVal()
    {
        return date("Y-m-d");
    }
}