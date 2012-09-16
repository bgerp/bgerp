<?php


/**
 * Клас  'type_Datetime' - Тип за време
 *
 *
 * @category  ef
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
    var $timePart = ' H:i';
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        $this->dt = cls::get('type_Date', $params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     * var $inputType   = 'datetime-local';
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        setIfNot($value, $attr['value']);

        if($value) {
            if(count($value) == 2) {
                $date = $value['d'];
                $time = $value['t'];
            } else {
                list($date, $time) = explode(' ', $value);
                $date = dt::mysql2verbal($date, 'd-m-Y');
                list($h, $m, $s) = explode(':', $time);
                if($s == '00') {
                    $time = "{$h}:{$m}";
                }
            }
        }
        
        $attr['size'] = 10;
        $attr['value'] = $date;
        $input = $this->dt->renderInput($name . '[d]', NULL, $attr);
        $attr['size'] = 6;
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
        
        $val1 = dt::verbal2mysql(trim(trim($value['d']) . ' ' . trim($value['t'])));
        
        $val2 = dt::verbal2mysql(dt::mysql2verbal($val1));
        

        if($val1 == $val2) {
            if(!trim($value['t'])) {
                $val1 = str_replace(' 00:00:00', '', $val1);
            }
            
            return $val1;
        } else {
             $this->error = "Не е в допустимите формати, като например|*: '<B>" . dt::mysql2verbal(NULL, 'd-m-Y G:i') . "</B>'";
            
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