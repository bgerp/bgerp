<?php



/**
 * Формат по подразбиране за времевата част
 */
defIfNot('EF_DATETIME_TIME_PART', ' H:i');


/**
 * Клас  'type_Datetime' - Тип за време
 *
 *
 * @category  all
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Datetime extends type_Date {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'datetime';
    
    
    /**
     * Формат на времевата част
     */
    var $timePart = EF_DATETIME_TIME_PART;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params)
    {
        parent::init($params);
        $this->dt = cls::get('type_Date', $params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     * var $inputType   = 'datetime-local';
     */
    function renderInput_($name, $value = "", $attr = array())
    {
        setIfNot($value, $attr['value']);
        
        if($value) {
            if(count($value) == 2) {
                $date = $value['d'];
                $time = $value['t'];
            } else {
                list($date, $time) = explode(' ', $this->toVerbal($value, FALSE));
            }
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
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        
        if(!is_array($value)) return NULL;
        
        if(!trim($value['d'])) return NULL;
        
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
     * Връща стойността по подразбиране за съответния тип
     */
    function defVal()
    {
        return date("Y-m-d h:i:s", 0);
    }
}