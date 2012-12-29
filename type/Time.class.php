<?php



/**
 * Клас  'type_Time' - Тип за продължителност от време
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
class type_Time extends type_Varchar {
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'int';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = '11';
    
    
    /**
     * Стойност по подразбиране
     */
    var $defaultValue = 0;
    
    
    /**
     * Атрибути на елемента "<TD>" когато в него се записва стойност от този тип
     */
    var $cellAttr = 'align="right"';
   
     
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($val)
    {
        $val = trim($val);
        
        // Празна стойност се приема за NULL
        if($val === '') return NULL;
        
        if(is_numeric($val)) {
            switch($this->params['uom']) {
                case 'weeks': 
                    $val = $val * 7 * 24 * 60;
                    break;
                case 'days':
                    $val = $val * 24 * 60;
                    break;
                case 'hours':
                    $val = $val * 60;
                    break;
                default:
                case 'minutes':
                    $val = $val;
                    break;
            }

            return round($val);
        }
        
        $val = strtolower(str::utf2ascii($val));
        
        // Проверка за стойности, означаващи 0, на момента, on time
        $zeroArr = array('на момента', 'веднага', 'on time');
        
        foreach($zeroArr as $w) {
            if($val == $w || $val == tr($w)) {
                return 0;
            }
        }
        
        //Извличаме секундите от текста
        if(preg_match(str::utf2ascii('/(\d+)[ ]*(s|secundes|sec|секунда|сек|с|секунди)\b/'), $val, $matches)) {
            $secundes = $matches[1];
        }
        
        //Извличаме минутите от текста
        if(preg_match(str::utf2ascii('/(\d+)[ ]*(m|minutes|min|минута|мин|м|минути)\b/'), $val, $matches)) {
            $minutes = $matches[1];
        }
        
        //Извличаме часовете от текста
        if(preg_match(str::utf2ascii('/(\d+)[ ]*(h|hours|ч|час|часа|часове)\b/'), $val, $matches)) {
            $hours = $matches[1];
        }
        
        //Извличаме дните от текста
        if(preg_match(str::utf2ascii('/(\d+)[ ]*(d|day|days|д|ден|дни|дена)\b/'), $val, $matches)) {
            $days = $matches[1];
        }
        
        //Извличаме седмиците от текста
        if(preg_match(str::utf2ascii('/(\d+)[ ]*(w|week|weeks|сед|седм|седмица|седмици)\b/'), $val, $matches)) {
            $weeks = $matches[1];
        }
        
        if(preg_match('/([\d]{1,2}):([\d]{1,2}):([\d]{1,2})\b/', $val, $matches)) {
            $hours = $matches[1];
            $minutes = $matches[2];
            $secundes = $matches[3];
        } elseif(preg_match('/([\d]{1,2}):([\d]{1,2})\b/', $val, $matches)) {
            // Извличаме информация за часове и минути във формат 23:50 
            $hours = $matches[1];
            $minutes = $matches[2];
        }
        
        if(strlen($secundes) || strlen($minutes) || strlen($hours) || strlen($days) || strlen($weeks)) {
            
            $duration = $secundes + 60 * $minutes + 60 * 60 * $hours + 24 * 60 * 60 * $days + 7 * 24 * 60 * 60 * $weeks;
            
            return $duration;
        } else {
            $this->error = 'Непознат формат за продължителност';
        }
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = '', &$attr = array())
    {
        if(is_numeric($value)) {
            $value = $this->toVerbal_($value);
        }

        if (!$this->suggestions) {
            if($this->params['suggestions']) {
                $suggestions = explode('|', $this->params['suggestions']);
                $this->suggestions[''] = '';
                if($value) {
                    $this->suggestions[$value] = $value;
                }
                foreach($suggestions as $opt) {
                    $this->suggestions[$opt] = $opt;
                }
            } else {
                $this->suggestions = array('' => '',
                    'на момента' => 'на момента',
                    '5 мин.'  => '5 мин.',
                    '10 мин.' => '10 мин.',
                    '15 мин.' => '15 мин.',
                    '30 мин.' => '30 мин.',
                    '45 мин.' => '45 мин.',
                    '45 мин.' => '45 мин.',
                    '1 час'   => '1 час',
                    '2 часа'  => '2 часа',
                    '8 часа'  => '8 часа',
                    '1 ден'   => '1 ден',
                    '2 дена'  => '2 дена',
                    '3 дена'  => '3 дена',
                    '7 дена'  => '7 дена');
            }
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal_($value)
    {
        if(!isset($value) || !is_numeric($value)) return NULL;
        
        $v = abs($value);
        

        
        $weeks    = floor($v / (7 * 24 * 60 * 60));
        $days     = floor(($v - $weeks * (7 * 24 * 60 * 60)) / (24 * 60 * 60));
        $hours    = floor(($v - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60)) / (60 * 60));
        $minutes  = floor(($v - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60) - $hours * 60 * 60) / 60);
        $secundes = floor(($v - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60) - $hours * 60 * 60 - $minutes * 60));
        
        if($format = $this->params['format']) {

            $repl['w'] = "$weeks";
            $repl['d'] = "$days";
            $repl['h'] = "$hours";
            $repl['H'] = sprintf('%02d', $hours);
            $repl['m'] = "$minutes";
            $repl['M'] = sprintf('%02d', $minutes);
            $repl['s'] = "$secundes";
            $repl['S'] = sprintf('%02d', $secundes);

            $res = str_replace(array_keys($repl), $repl, $format);

            return $res;
        }

        
        if($v == 0) {
            
            return '0 ' . tr('мин.');
        }

        if($weeks > 0) {
            if($days == 0) {
                $res .=  "{$weeks} сед.";
            } else {
                $days += $weeks * 7;
            }
        }
        
        if($days > 0) {
            if($days == 1) {
                $res .=   '1 ' . tr('ден');
            } elseif($days == 2) {
                $res .= '2 '  . tr('дена');
            } else {
                $res .= "{$days} " . tr('дни');
            }
        }
        
        if($hours > 0) {
            if($minutes > 0) {
                $res .= $res ? ', ' : '';
            } else {
                $res .= $res ? ' ' . tr('и') . ' ' : '';
            }
            $res .=  ($hours == 1) ? '1 ' . tr('час') : "{$hours} " . tr('часа');
        }
        
        if($minutes > 0) {
            $res .= $res ? ' ' . tr('и') . ' ' : '';
            
            $res .=   "{$minutes} " . tr('мин.');
        }
        
        if($secundes > 0) {
            $res .= $res ? ' ' . tr('и') . ' ' : '';
            
            $res .=   "{$secundes} " . tr('сек.');
        }
       
        return $res;
    }
}