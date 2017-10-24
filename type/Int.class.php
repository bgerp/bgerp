<?php



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
     * Клас за <td> елемент, който показва данни от този тип
     */
    var $tdClass = 'rightCol';
    
    
    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        parent::init($params);
        
        $this->params['allowHex'] = 'allowHex';
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($val)
    {
        $originalVal = $val;
        
        $from = array(',', ' ', "'", '`');
        
        $to = array('.', '', '', '');
        
        $val = str_replace($from, $to, trim($val));
        
        $allowOct = (boolean) ($this->params['allowOct'] == 'allowOct');
        $allowHex = (boolean) ($this->params['allowHex'] == 'allowHex');
		
        $val = $this->prepareVal($val, $allowOct, $allowHex);
        
        if($val === '') return NULL;
        
        // Превръщаме 16-тичните числа в десетични
        //$val = trim(preg_replace('/[^0123456789]{0,1}0x([a-fA-F0-9]*)/e', "substr('\\0',0,1).hexdec('\\0')", ' '.$val));
        
        // Ако имаме букви или др. непозволени символи - връщаме грешка
        if(preg_replace('`([^x+\-*=/\(\)\d\^<>&|\.]*)`', '', $val) != $val) {
            $this->error = "Недопустими символи в число/израз";
            
            return FALSE;
        }
        
        // Проверка да не сме препълнили int
        if (($val) && ($val > PHP_INT_MAX)) {
            $this->error = "Над допустимото|* " . $this->toVerbal(PHP_INT_MAX);
            
            return FALSE;
        }
        
        // Проверка да не сме препълнили int с отрицателни стойности
        if (($val) && ($val < ~PHP_INT_MAX)) {
            $this->error = "Под допустимото|* " . $this->toVerbal(~PHP_INT_MAX);
            
            return FALSE;
        }
        
        if(empty($val)) $val = '0';
        $code = "\$val = $val;";

        // Шаблон за намиране на повтарящи се знаци или изрази, които започват и/или завършват с тях
        $signP = '(\*|\/|\+|\-|\.|\,)';
        $pattern = "/(^(\s*(\*|\/)\s*))|({$signP}{1}\s*{$signP}+)|((\s*{$signP}\s*)$|([^\.|\,]*(\.|\,)[^{$signP}]*(\.|\,)[^\.|\,]*))|\=|[^0-9\(\)]{1}[^0-9\(\)]{1}/";
        
        if(!preg_match($pattern, $val) && @eval('return TRUE;' . $code)) {
            @eval($code);
            
            return (int) $val;
        } else {
            $this->error = "Грешка при превръщане на |*<b>'" . parent::escape($originalVal) . "'</b> |в число";
            
            return FALSE;
        }
    }
    
    
    /**
     * Връща атрибутите на MySQL полето
     */
    function getMysqlAttr()
    {
        $size = $this->getDbFieldSize();
        
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
        setIfNot($this->params[0], $this->params['size'], 11);
        
        setIfNot($attr['maxlength'], 16);
        
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
        
        $conf = core_Packs::getConfig('core');
        
        if(strlen($value) > 4) {
            $ts = Mode::is('forSearch') ? '' : $conf->EF_NUMBER_THOUSANDS_SEP;
            $value = number_format($value, 0, html_entity_decode($conf->EF_NUMBER_DEC_POINT), html_entity_decode($ts));
        }
        
    	if(!Mode::is('text', 'plain')) {
            $value = str_replace(' ', '&nbsp;', $value);
        }
        
        return $value;
    }
    
    
    /**
     * Връща стойността по подразбиране за съответния тип
     */
    function defVal()
    {
        return 0;
    }
    
    
    /**
     * Проверява дали стойността е int
     * 
     * @param string $val
     * @param boolean $unsigned
     * 
     * @return boolean
     */
    public static function isInt($val, $unsigned = FALSE)
    {
        if (!isset($val)) return FALSE;
        
        $val = trim($val);
        
        if ($unsigned) {
            $pattern = '/^\d+$/';
        } else {
            $pattern = '/^-?\d+$/';
        }
        
        if (preg_match($pattern, $val)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Премахва символите за осмична и шестнайсетична бройна система, ако не са позволени
     * 
     * @param integer $number
     * @param boolean $allowOct
     * @param boolean $allowHex
     * 
     * @return string
     */
    protected function prepareVal($number, $allowOct = FALSE, $allowHex = FALSE)
    {
        if (!$number) return $number;
        
        if ($allowOct && $allowHex) return $number;
        
        if (!$allowOct && !$allowHex) {
            $pattern = '0|0x';
        } elseif (!$allowOct) {
            $pattern = '0';
        } else {
            $pattern = '0x';
        }
        
        $number = str_replace(' ', '', $number);
        
        $pattern = "/(^|[^0-9]+)({$pattern})+([0-9]+)/";
        
        $number = preg_replace($pattern, "$1$3", $number);
        
        return $number;
    }
}
