<?php



/**
 * Формат по подразбиране за датите
 */
defIfNot('EF_DATE_FORMAT', 'd-m-YEAR');


/**
 * Формат по подразбиране за датата при тесни екрани
 */
defIfNot('EF_DATE_NARROW_FORMAT', 'd-m-year');


/**
 * Клас  'type_Date' - Тип за дати
 *
 *
 * @category  all
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Date extends core_Type {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'date';
    
    
    /**
     * Атрибути на елемента "<TD>" когато в него се записва стойност от този тип
     */
    var $cellAttr = 'align="center" nowrap';
    
    
    /**
     * Формат на времевата част
     */
    var $timePart = '';
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value, $useFormat = TRUE)
    {
        if(empty($value)) return NULL;
        
        if($this->params['format'] && !Mode::is('printing') && (Mode::is('text', 'html') || !Mode::is('text')) && $useFormat) {
            $format = $this->params['format'];
        } elseif(Mode::is('screenMode', 'narrow')) {
            $format = EF_DATE_NARROW_FORMAT . $this->timePart;
        } else {
            $format = EF_DATE_FORMAT . $this->timePart;
        }
        
        $date = dt::mysql2verbal($value, $format);
        
        return $date;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        $value = trim($value);
        
        if(empty($value)) return NULL;
        
        $value = dt::verbal2mysql($value, !empty($this->timePart));
        
        if($value) {
            
            return $value;
        } else {
            $now = $this->toVerbal(dt::verbal2mysql('', !empty($this->timePart)));
            $this->error = "Не е в допустимите формати, като например|*: '<B>{$now}</B>'";
            
            return FALSE;
        }
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", $attr = array())
    {
        $attr['name'] = $name;
        
        setIfNot($attr['size'], 20);
        
        if($value) {
            $value = $this->toVerbal($value);
        } else {
            $value = $attr['value'];
        }
        
        return $this->createInput($name, $value, $attr);
    }
    
    
    /**
     * Връща стойността по подразбиране за съответния тип
     */
    function defVal()
    {
        return date("Y-m-d", 0);
    }
}