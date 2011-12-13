<?php

/**
 * Формат по подразбиране за датите
 */
defIfNot('EF_DATE_FORMAT', 'd-m-YEAR');


/**
 * Формат по подразбиране за датата при тесни екрани
 */
defIfNot('EF_DATE_NARROW_FORMAT', 'd-m-year');


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
     *  MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'date';
    
    
    /**
     *  Атрибути на елемента "<TD>" когато в него се записва стойнос от този тип
     */
    var $cellAttr = 'align="center" nowrap';
    
    
    /**
     *  Формат на времевата част
     */
    var $timePart = '';
    
    
    /**
     *  @todo Чака за документация...
     */
    function toVerbal($value, $time = '')
    {
        if(empty($value)) return NULL;
        
        if($this->params['format'] && !Mode::is('printing')) {
            $format = $this->params['format'];
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
        $value = trim($value);
        if(empty($value)) return NULL;

        $value = dt::verbal2mysql($value, !empty($this->timePart));
        
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