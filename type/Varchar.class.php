<?php



/**
 * Клас  'type_Varchar' - Тип за символни последователности (стринг)
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
class type_Varchar extends core_Type {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'varchar';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 255;
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        // Сигнализиране на потребителя, ако въведе по-дълъг текст от допустимото
        setIfNot($size, $this->params['size'], $this->params[0], $this->dbFieldLen);
        
        if (!$this->params['noTrim']) {
            $attr['onblur'] .= "this.value = this.value.trim();";
        }
        
        if($size > 0) {
            
            $attr['onblur'] .= "colorByLen(this, {$size}, true); if(this.value.length > {$size}) alert('" . 
                 tr("Въведената стойност е дълга") . " ' + this.value.length + ' " . tr("символа, което е над допустимите") . " $size " . tr('символа') . "');";
            $attr['onkeyup'] .= "colorByLen(this, {$size});";
        }
        
        if($this->inputType) {
            $attr['type'] = $this->inputType;
        }
        
        if($this->params['readonly']) {
            $attr['readonly'] = 'readonly';
        }

        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
    
    
	/**
     * Този метод трябва да конвертира от вербално към вътрешно
     * представяне дадената стойност
     * 
     * 
     */
    function fromVerbal_($value)
    {
        //Ако няма параметър noTrim, тогава тримваме стойността
        if (!$this->params['noTrim']) {
            
            //Тримвано стойността
             $value= trim($value);
        }
        
        // За някои случаи вместо празен стринг е по-добре да получаваме NULL
        if($this->params['nullIfEmpty'] || $this->nullIfEmpty) {
            if(!$value) {
                $value = NULL;
            }
        }

        $value = parent::fromVerbal_($value);

        return $value;
    }
          
    
	/**
     * Този метод трябва да конвертира от вътрешно към вербално
     * представяне дадената стойност
     */
    function toVerbal_($value)
    {
        $res = parent::toVerbal_($value);
        
        if (Mode::is('htmlEntity', 'none')) {
            $res = html_entity_decode($res, ENT_QUOTES, 'UTF-8');
        }
        
        return $res;
    }
}
