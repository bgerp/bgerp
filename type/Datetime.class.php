<?php


/**
 * Клас  'type_Datetime' - Тип за време
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Datetime extends type_Date
{
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'datetime';
    
    
    /**
     * Формат на времевата част
     */
    public $timePart = ' H:i';
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        $this->dt = cls::get('type_Date', $params);
        if (!isset($this->params['defaultTime'])) {
            $this->params['defaultTime'] = '00:00:00';
        }
    }
    
    
    /**
     * Рендира HTML инпут поле
     * var $inputType   = 'datetime-local';
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        setIfNot($value, $attr['value']);
        
        if ($value) {
            if (is_array($value)) {
                $date = $value['d'];
                $time = $value['t'];
            } elseif (is_scalar($value)) {
                list($date, $time) = explode(' ', $value);
                $date = dt::mysql2verbal($date, 'd.m.Y', null, false);
                list($h, $m, $s) = explode(':', $time);
                if ($s == '00') {
                    $time = "{$h}:{$m}";
                }
            }
        }
        
        if (strlen($time) && strpos($this->params['defaultTime'], $time) === 0) {
            $time = '';
        }
        
        $attr['value'] = $date;
        $input = $this->dt->renderInput($name . '[d]', null, $attr);
        $input->append('&nbsp;');
        
        $attr['value'] = $time;
        $attr['autocomplete'] = 'off';
        $attr['style'] .= ';vertical-align:top;';
        unset($attr['id']);
        
        if (strlen($time) == 5 || strlen($time) == 0) {
            $sugArr = explode('|', '08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00');
            $sugArr[] = $time;
            sort($sugArr);
            $sugList = implode('|', $sugArr);
            
            setIfNot($ts, $this->params['timeSuggestions'], $sugList);
            
            if (!is_array($ts)) {
                $ts = array('' => '') + arr::make(str_replace('|', ',', $ts), true);
            }
            $attr['style'] .= ';max-width:4em;';
        } elseif (strlen($time) == 8) {
            $sugArr = explode('|', '08:00:00|09:00:00|10:00:00|11:00:00|12:00:00|13:00:00|14:00:00|15:00:00|16:00:00|17:00:00|18:00:00');
            $sugArr[] = $time;
            sort($sugArr);
            $sugList = implode('|', $sugArr);
            
            setIfNot($ts, $this->params['timeSuggestions'], $sugList);
            
            if (!is_array($ts)) {
                $ts = array('' => '') + arr::make(str_replace('|', ',', $ts), true);
            }
            $attr['style'] .= ';max-width:6em;';
        } else {
            $ts = array('' => '', $time => $time);
        }
        
        $timeInput = ht::createCombo($name . '[t]', $time, $attr, $ts);
        
        $input->append($timeInput);
        
        return $input;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($valueIn)
    {
        if (is_scalar($valueIn)) {
            $value = array();
            list($value['d'], $value['t']) = explode(' ', $valueIn);
        } elseif (is_array($valueIn)) {
            $value = $valueIn;
        }
        
        if (!trim($value['d']) && trim($value['t'])) {
            $value['d'] = date('d-m-Y');
        }
        
        $time = trim($value['t']);
        
        if (!strlen($time) && strlen($value['d'])) {
            $time = $this->params['defaultTime'];
        }
        
        $val1 = trim(trim($value['d']) . ' ' . $time);
        
        
        if (!$val1) {
            
            return;
        }
        
        $val2 = dt::verbal2mysql($val1);
        
        if ($val2) {
            if ($val2 < '1970-01-01 02:00:00' || $val2 > '2038-01-01 00:00:00') {
                $this->error = 'Извън UNIX ерата|*: <B>1970 - 2038</B>';
                
                return false;
            }
            
            return $val2;
        }
        $this->error = "Не е в допустимите формати, като например|*: '<B>" . dt::mysql2verbal(null, 'd-m-Y G:i', null, false) . "</B>'";
        
        return false;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function toVerbal($value, $useFormat = true)
    {
        list($d, $t) = explode(' ', $value);
        
        $stp = $this->timePart;
        $sf = $this->params['format'];
        
        if ($t == $this->params['defaultTime']) {
            $this->timePart = '';
            if ($this->params['format'] == 'smartTime') {
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
    public function defVal()
    {
        return date('Y-m-d h:i:s', 0);
    }
}
