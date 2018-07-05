<?php



/**
 * Клас  'color_Type' - Тип за  цвят
 *
 *
 * @category  vendors
 * @package   color
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class color_Type extends type_Varchar
{
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 10;
    

    /**
     * Колко символа е дълго полето в базата
     */
    public $dbFieldLen = 9;

    
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    public $tdClass = 'centerCol';
    
    
    /**
     * @todo Чака за документация...
     */
    public function toVerbal($value)
    {
        if (!trim($value)) {
            return;
        }
        
        $cObj = new color_Object($value);
        
        $bgColor = $cObj->getHex();
        
        $color = "<span class='colorName'>".tr($value) . "</span><span class='colorBox' style=\"background-color:{$bgColor} !important;\"></span>";
    
        return $color;
    }
    
    
    /**
     * Този метод трябва да конвертира от вербално към вътрешно представяне дадената стойност
     */
    public function fromVerbal($value)
    {
        if (!trim($value)) {
            return;
        }
        
        $cObj = new color_Object($value);
        
        if ($this->error = $cObj->error) {
            return false;
        }
            
        return $cObj->getHex();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $attr['name'] = $name;
        
        setIfNot($attr['size'], 10);
        
        if ($value) {
            $value = $value;
        } else {
            $value = $attr['value'];
        }

 
        return $this->createInput($name, $value, $attr);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function defVal()
    {
        return '#ffffff';
    }
}
