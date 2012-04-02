<?php



/**
 * Кой символ да използваме за разделител на хилядите?
 */
defIfNot('EF_NUMBER_THOUSANDS_SEP', ' ');


/**
 * Клас  'type_Int' - Тип за цели числа
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
class type_Int extends core_Type {
    
    
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
        $originalVal = $val;
        
        $from = array(',', EF_TYPE_DOUBLE_DEC_POINT, ' ', "'", EF_TYPE_DOUBLE_THOUSANDS_SEP);
        
        $to = array('.', '.', '', '', '');
        
        $val = str_replace($from, $to, trim($val));
        
        if($val === '') return NULL;
        
        // Превръщаме 16-тичните числа в десетични
        //$val = trim(preg_replace('/[^0123456789]{0,1}0x([a-fA-F0-9]*)/e', "substr('\\0',0,1).hexdec('\\0')", ' '.$val));
        
        // Ако имаме букви или др. непозволени символи - връщаме грешка
        if(preg_replace('`([^+\-*=/\(\)\d\^<>&|\.]*)`', '', $val) != $val) {
            $this->error = "Недопустими символи в число/израз";
            
            return FALSE;
        }
        
        if(empty($val)) $val = '0';
        $code = "\$val = $val;";
        
        if(@eval('return TRUE;' . $code)) {
            eval($code);
            
            return (int) $val;
        } else {
            $this->error = "Грешка при превръщане на |*<b>'{$originalVal}'</b> |в число";
            
            return FALSE;
        }
    }
    
    
    /**
     * Връща атрибутите на MySQL полето
     */
    function getMysqlAttr()
    {
        $size = $this->params['size'] ? $this->params['size'] : $this->params[0] ;
        
        if(!$size || $size <= 11) {
            $this->dbFieldType = "INT";
        } else {
            $this->dbFieldType = "BIGINT";
        }
        
        if(isset($this->params['min']) && $this->params['min'] >= 0) {
            $this->params['unsigned'] = 'unsigned';
        }
        
        return parent::getMysqlAttr();
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = '', &$attr = array())
    {
        setIfNot($attr['size'],
            $this->params[0],
            $this->params['size'],
            Mode::is('screenMode', 'narrow') ? 10 : 20
        );
        
        setIfNot($attr['maxlen'], 16);
        
        // В мобилен режим слагаме тип = number, за да форсираме цифрова клавиатура
        if(Mode::is('screenMode', 'narrow') && empty($attr['type'])) {
            $attr['type'] = 'number';
        }
        
        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal_($value)
    {
        if(!isset($value)) return NULL;
        
        $thousandsSep = EF_NUMBER_THOUSANDS_SEP;
        
        if(strlen($value) > 4) {
            $value = number_format($value, 0, '', $thousandsSep);
        }
        
        return str_replace(' ', '&nbsp;', $value);
    }
    
    
    /**
     * Връща стойността по подразбиране за съответния тип
     */
    function defVal()
    {
        return 0;
    }
}