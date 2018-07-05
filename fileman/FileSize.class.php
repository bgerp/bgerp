<?php

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
class fileman_FileSize extends type_Bigint
{
    
    
    /**
     * @todo Чака за документация...
     */
    public $sizes = array('Bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    
    
    /**
     * @todo Чака за документация...
     */
    public function toVerbal_($size)
    {
        if ($size === null) {
            return;
        }
        
        $space = '&nbsp;';

        if (Mode::is(text, 'plain')) {
            $space = ' ';
        }

        if ($size == 0) {
            return($space . 'Bytes');
        }

        return (round($size / pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $space . $this->sizes[$i]);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if ($this->params[0]) {
            $attr['maxlength'] = $this->params[0];
        }
        
        if ($this->params['size']) {
            $attr['size'] = $this->params['size'];
        }
        
        $this->fromVerbalSuggestions($value);
        
        Mode::push('text', 'plain');
        $value = $this->error ? $value : $this->toVerbal($value);
        Mode::pop('text');

        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне
     */
    public function fromVerbal_($value)
    {
        if ($value === null) {
            return;
        }
        
        if ($value === 0) {
            return 0;
        }
        
        if ((round(trim($value)) . '') == trim($value)) {
            $value .= 'b';
        }
        
        $sizesFrom = array('BYTES', 'KB', 'MB', 'GB', 'TB', 'БАЙТА', 'КБ', 'МБ', 'ГБ', 'ТБ', 'B', 'Б', 'К', 'K', 'М', 'M', 'Г', 'G', 'Т', 'T');
        $sizesTo = array('-1', '-1024', '-1048576', '-1073741824', '-1099511627776', '-1', '-1024', '-1048576', '-1073741824', '-1099511627776', '-1', '-1', '-1024', '-1024', '-1048576', '-1048576', '-1073741824', '-1073741824', '-1073741824', '-1073741824');
        
        $value1 = str_replace($sizesFrom, $sizesTo, mb_strtoupper($value));
        
        $arr = explode('-', $value1);
        
        $res = trim($arr[0]) * trim($arr[1]);
        
        if (!$res && $value > 0) {
            $this->error = 'Некоректен размер на файл';
            
            return false;
        }
        
        return $res;
    }
}
