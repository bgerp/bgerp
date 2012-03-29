<?php



/**
 * Клас  'color_Type' - Тип за  цвят
 *
 *
 * @category  all
 * @package   color
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class color_Type extends type_Varchar {
    
    
    /**
     * @todo Чака за документация...
     */
    var $cellAttr = 'align="center" nowrap';
    
    
    /**
     * @todo Чака за документация...
     */
    function toVerbal($value)
    {
        if(!trim($value)) return NULL;
        
        $cObj = new color_Object($value);
        
        $bgColor = $cObj->getHex();
        
        $color = " $value<span style='background-color:{$bgColor}; border:solid 1px #333;margin:2px;'>&nbsp;&nbsp;</span>  ";
        
        return $color;
    }
    
    
    /**
     * Този метод трябва да конвертира от вербално към вътрешно представяне дадената стойност
     */
    function fromVerbal($value)
    {
        if(!trim($value)) return NULL;
        
        $cObj = new color_Object($value);
        
        if($this->error = $cObj->error) {
            
            return FALSE;
        } else {
            
            return $cObj->getHex();
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $attr['name'] = $name;
        
        setIfNot($attr['size'], 10);
        
        if($value) {
            $value = $value;
        } else {
            $value = $attr['value'];
        }
        
        return $this->createInput($name, $value, $attr);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function defVal()
    {
        return '#ffffff';
    }
}