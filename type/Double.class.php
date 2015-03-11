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
        
        if ((int)$value !== 0) {
            
            if (isset($this->params['allowOct']) && $this->params['allowOct'] != 'allowOct') {
                if ($value{1} != 'x') {
                    $value = ltrim($value, 0);
                }
            }
                
            if (isset($this->params['allowHex']) && $this->params['allowHex'] != 'allowHex') {
                $value = ltrim($value, '0x');
            }
        }
        
        if(!strlen($value)) return NULL;
        
        $originalVal = $value;
        
        $from = array(',', '.', ' ', "'", '`');
        
        $to = array('.', '.', '', '', '');
        
        $value = str_replace($from, $to, trim($value));
        
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
        
        if(@eval('return TRUE;' . $code)) {
            eval($code);
            
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
        $decPoint = html_entity_decode($conf->EF_NUMBER_DEC_POINT);
        $thousandsSep = html_entity_decode($conf->EF_NUMBER_THOUSANDS_SEP);
        
        setIfNot($decimals, $this->params['decimals'], EF_NUMBER_DECIMALS);
        
        // Ако закръгляме умно
        if($this->params['smartRound']){
        	
        	// Закръгляме до минимума от символи от десетичния знак или зададения брой десетични знака
        	$decimals = min(strlen(substr(strrchr($value, '.'), 1)), $decimals);
        }

        // Закръгляме числото преди да го обърнем в нормален вид
        $value = round($value, $decimals);
        
        $value = number_format($value, $decimals, $decPoint, $thousandsSep);
        
        if(!Mode::is('text', 'plain')) {
            $value = str_replace(' ', '&nbsp;', $value);
        }
        
        return $value;
    }
    
}