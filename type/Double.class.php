<?php


/**
 * Колко цифри след запетаята да показваме по подразбиране?
 */
defIfNot('EF_NUMBER_DECIMALS', 4);


/**
 * Клас 'type_Double' - Тип за рационални числа
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
class type_Double extends core_Type {
    
    
    /**
     * Тип на полето в mySql таблица
     */
    var $dbFieldType = 'double';
    
    
    /**
     * Стойност по подразбиране
     */
    var $defaultValue = 0;
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    var $maxFieldSize = 12;


    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    var $tdClass = 'rightCol';


    /**
     * Намира стойността на числото, от стринга, който е въвел потребителя
     * Входния стринг може да не е форматиран добре, също може да съдържа прости
     * аритметически изрази
     */
    function fromVerbal($value)
    {
        $value = trim($value);
        
        $allowOct = (boolean) ($this->params['allowOct'] == 'allowOct');
        $allowHex = (boolean) ($this->params['allowHex'] == 'allowHex');
        
        
        $originalVal = $value;
        
        $from = array(',', '.', ' ', "'", '`');
        
        $to = array('.', '.', '', '', '');
        
        $value = str_replace($from, $to, trim($value));
        
        if(!strlen($value)) return NULL;
        
        $value = $this->prepareVal($value, $allowOct, $allowHex);
        
        if(!strlen($value)) return NULL;
        
        // Превръщаме 16-тичните числа в десетични
        //$value = trim(preg_replace('/[^0123456789]{0,1}0x([a-fA-F0-9]*)/e', "substr('\\0',0,1).hexdec('\\0')", ' '.$value));
        
        // Ако имаме букви или др. непозволени символи - връщаме грешка
        if(preg_replace('`([^+x\-*=/\(\)\d\^<>&|\.]*)`', '', $value) != $value) {
            $this->error = "Недопустими символи в число/израз";
            
            return FALSE;
        }
        
        if(empty($value)) $value = '0';
        $code = "\$val = $value;";

        // Шаблон за намиране на повтарящи се знаци или изрази, които започват и/или завършват с тях
        $signP = '(\*|\/|\+|\-|\.|\,)';
        $pattern = "/(^(\s*(\*|\/)\s*))|({$signP}{1}\s*{$signP}+)|((\s*{$signP}\s*)$|([^\.|\,]*(\.|\,)[^{$signP}]*(\.|\,)[^\.|\,]*))|\=|[^0-9\(\)]{1}[^0-9\(\)]{1}/";
        
        if(!preg_match($pattern, $value) && @eval('return TRUE;' . $code)) {
            @eval($code);
            
            return (float) $val;
        } else {
            $this->error = "Грешка при превръщане на |*<b>'" . parent::escape($originalVal) . "'</b> |в число";
            
            return FALSE;
        }
    }
    
    
    /**
     * Генерира input-поле за числото
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if($this->params[0] + $this->params[1] > 0) {
            $attr['size'] = $this->params[0] + $this->params[1] + 1;
        }
        
        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal($value)
    {
        if(!strlen($value)) return NULL;
        
        $conf = core_Packs::getConfig('core');

        if(!$this->params['decPoint']) {
            $this->params['decPoint'] = html_entity_decode($conf->EF_NUMBER_DEC_POINT);
        }

        if(!$this->params['thousandsSep']) {
            $this->params['thousandsSep'] = html_entity_decode($conf->EF_NUMBER_THOUSANDS_SEP);
        }
        
        
        if(!isset($this->params['decimals'])) {
            $this->params['decimals'] = $this->params['decimals'];
        }
        
        if(!isset($this->params['decimals'])) {
        	$this->params['decimals'] = EF_NUMBER_DECIMALS;
        }
        
        // Ако закръгляме умно
        if($this->params['smartRound']){
        	$oldDecimals = $this->params['decimals'];
        	
        	// Закръгляме до минимума от символи от десетичния знак или зададения брой десетични знака
        	$this->params['decimals'] = min(strlen(substr(strrchr($value, '.'), 1)), $this->params['decimals']);
        }

        // Закръгляме числото преди да го обърнем в нормален вид
        $value = round($value, $this->params['decimals']);
        $ts = Mode::is('forSearch') ? '' : $this->params['thousandsSep'];
        $value = number_format($value, $this->params['decimals'], $this->params['decPoint'], $ts);
        
        if(!Mode::is('text', 'plain')) {
            $value = str_replace(' ', '&nbsp;', $value);
        }
        
        if($this->params['smartRound']){
        	// След умното закръгляне, връщаме старата стойност за брой десетични знаци.
        	// Така се подсигуряваме че след последователно викане на стойноста винаги ще се изчислява на момента
        	$this->params['decimals'] = $oldDecimals;
        }
        
        return $value;
    }
    
    
    /**
     * Премахва символите за осмична и шестнайсетична бройна система, ако не са позволени
     * 
     * @param string $double
     * @param boolean $allowOct
     * @param boolean $allowHex
     * 
     * @return string
     */
    protected function prepareVal($double, $allowOct = FALSE, $allowHex = FALSE)
    {
        if (!$double) return $double;
        
        if ($allowOct && $allowHex) return $double;
        
        if (!$allowOct && !$allowHex) {
            $pattern = '0|0x';
        } elseif (!$allowOct) {
            $pattern = '0';
        } else {
            $pattern = '0x';
        }
        
        $double = str_replace(' ', '', $double);
        
        $pattern = "/(^|[^\.0-9]+)({$pattern})+([0-9][\.0-9]*)/";
        
        $double = preg_replace($pattern, "$1$3", $double);
        
        return $double;
    }
}
