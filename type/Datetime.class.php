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
                $date = dt::mysql2verbal($date, 'd.m.Y', NULL, FALSE);
                list($h, $m, $s) = explode(':', $time);
                if($s == '00') {
                    $time = "{$h}:{$m}";
                }
            }
        }
        
        $attr['value'] = $date;
        $input = $this->dt->renderInput($name . '[d]', NULL, $attr);
        $input->append('&nbsp;');
        
        $attr['value'] = $time;
        $attr['style'] .= ';vertical-align:top; max-width:4em;';
        unset($attr['id']);
        
        if(strlen($time) == 5 || strlen($time) == 0) {
            $sugArr = explode('|', '08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00');
            $sugArr[] = $time;
            sort($sugArr);
            $sugList = implode('|', $sugArr);

            setIfNot($ts, $this->params['timeSuggestions'], $sugList);
            
            if(!is_array($ts)) {
                $ts = array('' => '') + arr::make(str_replace('|', ',', $ts), TRUE);
            }
        } elseif(strlen($time) == 8){
        	$sugArr = explode('|', '08:00:00|09:00:00|10:00:00|11:00:00|12:00:00|13:00:00|14:00:00|15:00:00|16:00:00|17:00:00|18:00:00');
        	$sugArr[] = $time;
        	sort($sugArr);
        	$sugList = implode('|', $sugArr);
        	
        	setIfNot($ts, $this->params['timeSuggestions'], $sugList);
        	
        	if(!is_array($ts)) {
        		$ts = array('' => '') + arr::make(str_replace('|', ',', $ts), TRUE);
        	}
        }else {
        	
            $ts = array('' => '', $time => $time);
        }

        $timeInput = ht::createCombo($name . '[t]', $time, $attr, $ts);

        $input->append($timeInput);

        return $input;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        if(!is_array($value)) return NULL;
    
        if(!trim($value['d']) && trim($value['t'])) {
            $value['d'] = date('d-m-Y');
        }

        $val1 = trim(trim($value['d']) . ' ' . trim($value['t']));
        
        if(!$val1) return NULL;

        $val2 = dt::verbal2mysql($val1);
         
        if($val2) {
            if(!trim($value['t'])) {
                $val2 = str_replace(' 00:00:00', '', $val2);
            }

            if($val2 < '1970-01-01 02:00:00' || $val2 > '2038-01-01 00:00:00') {
                $this->error = "Извън UNIX ерата|*: <B>1970 - 2038</B>";
                
                return FALSE;
            }

            return $val2;
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