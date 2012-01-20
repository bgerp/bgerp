<?php

// Тип данни за размер на файл

cls::load('type_Int');


/**
 * Клас 'fileman_FileSize' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_FileSize extends type_Int {
    
    
    /**
     * @todo Чака за документация...
     */
    var $sizes = array("&nbsp;Bytes", "&nbsp;kB", "&nbsp;MB", "&nbsp;GB", "&nbsp;TB", "&nbsp;PB", "&nbsp;EB", "&nbsp;ZB", "&nbsp;YB");
    
    
    /**
     * @todo Чака за документация...
     */
    function toVerbal($size)
    {
        if($size === NULL) return NULL;
        
        if ($size == 0) {
            return('0&nbsp;Bytes');
        } else {
            return (round($size / pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $this->sizes[$i]);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderInput_($name, $value = "", $attr = array())
    {
        if($this->params[0]) {
            $attr['maxlength'] = $this->params[0];
        }
        
        if($this->params['size']) {
            $attr['size'] = $this->params['size'];
        }
        
        $tpl = ht::createTextInput($name, $this->toVerbal($value), $attr);
        
        return $tpl;
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне
     */
    function fromVerbal($value)
    {
        if($value === NULL) return NULL;
        
        if($value === 0) return 0;
        
        if((round(trim($value)) . '') == trim($value)) {
            $value .= 'b';
        }
        
        $sizesFrom = array('BYTES', 'KB', 'MB', 'GB', 'TB', 'БАЙТА', 'КБ', 'МБ', 'ГБ', 'ТБ', 'B', 'Б', 'К', 'K', 'М', 'M', 'Г', 'G', 'Т', 'T');
        $sizesTo = array('-1', '-1024', '-1048576', '-1073741824', '-1099511627776', '-1', '-1024', '-1048576', '-1073741824', '-1099511627776', '-1', '-1', '-1024', '-1024', '-1048576', '-1048576', '-1073741824', '-1073741824', '-1073741824', '-1073741824');
        
        $value1 = str_replace($sizesFrom, $sizesTo, mb_strtoupper($value));
        
        $arr = explode('-', $value1);
        
        $res = trim($arr[0]) * trim($arr[1]);
        
        if(!$res && $value>0) {
            $this->error = 'Некоректен размер на файл';
            
            return FALSE;
        }
        
        return $res;
    }
}