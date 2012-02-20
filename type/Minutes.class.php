<?php

/**
 * Клас  'type_Minutes' - Тип за продължителност от време в минути
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
class type_Minutes extends type_Int {

    /**
     * Атрибути на елемента "<TD>" когато в него се записва стойнос от този тип
     */
    var $cellAttr = 'align="center"';
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($val)
    {
        $val = trim($val);
        
        // Празна стойност се приема за NULL
        if($val === '') return NULL;

        if(is_numeric($val)) {
            return round($val);
        }

        $val = mb_strtolower($val);

        // Проверка за стойности, означаващи 0, на момента, on time
        $zeroArr = array('на момента', 'веднага', 'on time');
        foreach($zeroArr as $w) {
            if($val == $w || $val == tr($w)) {
                return 0;
            }
        }

        //Извличаме минутите от текста
        if(preg_match('/(\d+)[ ]*(m|minutes|min|мин|м|минути)\b/u', $val, $matches)) {
            $minutes = $matches[1];
        }

        //Извличаме часовете от текста
        if(preg_match('/(\d+)[ ]*(h|hours|ч|час|часа|часове)\b/u', $val, $matches)) {
            $hours = $matches[1];  
        }
     
        //Извличаме дните от текста
        if(preg_match('/(\d+)[ ]*(d|day|days|д|ден|дни|дена)\b/u', $val, $matches)) {
            $days = $matches[1];
        }
        
        //Извличаме седмиците от текста
        if(preg_match('/(\d+)[ ]*(w|week|weeks|сед|седм|седмица|седмици)\b/u', $val, $matches)) {
            $weeks = $matches[1];
        }

        // Извличаме информация за часове и минути във формат 23:50 
        if(preg_match('/([\d]{1,2}):([\d]{1,2})\b/', $val, $matches)) {
            $hours = $matches[1];
            $minutes = $matches[2];
        }
        
        if($minutes != 0 || $hours != 0 || $days != 0 || $weeks != 0) {

            $duration = $minutes + 60*$hours + 24*60*$days + 7*24*60*$weeks;

            return $duration;
        } else {
            $this->error = 'Непознат формат за продължителност';
        }
    }
    
     
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value, $attr = array())
    {
        $this->suggestions = array('' => '', 'на момента' => 'на момента');

        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal_($value)
    {
        if(!isset($value) || !is_numeric($value)) return NULL;
        
        $v = abs($value);
        
        if($v == 0) {

            return '0 ' . tr('мин.');
        }

        $weeks   = floor($v / (7*24*60));
        $days    = floor(($v - $weeks * (7*24*60)) / (24*60));
        $hours   = floor(($v - $weeks * (7*24*60) - $days * (24*60)) / 60);
        $minutes = floor(($v - $weeks * (7*24*60) - $days * (24*60) - $hours*60));

        if($weeks > 0 && $days == 0) {
            $res .=  "{$weeks} сед.";
        }
        
        if($days > 0) {
            $res .=  ($days == 1) ? '1 ' . tr('ден') : "{$days} " . tr('дни');
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

        return $res;
    }
    
}