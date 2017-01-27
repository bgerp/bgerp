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
        if(!isset($this->params['defaultTime'])) {
            $this->params['defaultTime'] = '00:00:00';
        }
    }
    
    
    /**
     * Рендира HTML инпут поле
     * var $inputType   = 'datetime-local';
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        setIfNot($value, $attr['value']);

        if($value) {
            if(is_array($value)) {
                $date = $value['d'];
                $time = $value['t'];
            } elseif(is_scalar($value)) {
                list($date, $time) = explode(' ', $value);
                $date = dt::mysql2verbal($date, 'd.m.Y', NULL, FALSE);
                list($h, $m, $s) = explode(':', $time);
                if($s == '00') {
                    $time = "{$h}:{$m}";
                }
            }
        }

        if(strlen($time) && strpos($this->params['defaultTime'], $time) === 0) {
            $time = '';
        }
        
        $attr['value'] = $date;
        $input = $this->dt->renderInput($name . '[d]', NULL, $attr);
        $input->append('&nbsp;');
        
        $attr['value'] = $time;
        $attr['autocomplete'] = "off";
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
    function fromVerbal($valueIn)
    {
        if(is_scalar($valueIn)) {
            $value = array();
            list($value['d'], $value['t']) = explode(' ', $valueIn);
        } elseif(is_array($valueIn)) {
            $value = $valueIn;
        }
    
        if(!trim($value['d']) && trim($value['t'])) {
            $value['d'] = date('d-m-Y');
        }

        $time = trim($value['t']);

        if(!strlen($time) && strlen($value['d'])) {
            $time = $this->params['defaultTime'];
        }

        $val1 = trim(trim($value['d']) . ' ' . $time);
        

        if(!$val1) return NULL;

        $val2 = dt::verbal2mysql($val1);
         
        if($val2) {
 
            if($val2 < '1970-01-01 02:00:00' || $val2 > '2038-01-01 00:00:00') {
                $this->error = "Извън UNIX ерата|*: <B>1970 - 2038</B>";
                
                return FALSE;
            }

            return $val2;
        } else {
            $this->error = "Не е в допустимите формати, като например|*: '<B>" . dt::mysql2verbal(NULL, 'd-m-Y G:i', NULL, FALSE) . "</B>'";
            
            return FALSE;
        }
    }
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value, $useFormat = TRUE)
    {
    	list($d, $t) = explode(' ', $value);

        $stp = $this->timePart;
        $sf = $this->params['format'];
        
        if($t == $this->params['defaultTime']) {
            $this->timePart = '';
            if($this->params['format'] == 'smartTime') {
                $this->params['format'] = 'smartDate';
            }
        }
    	
        $res = parent::toVerbal($value, $useFormat);

        $this->timePart = $stp;
        $this->params['format'] = $sf;

        return $res;
    }


    /**
     * Връща стойността по подразбиране за съответния тип
     */
    function defVal()
    {
        return date("Y-m-d h:i:s", 0);
    }
}