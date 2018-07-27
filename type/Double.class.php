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
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Double extends core_Type
{
    /**
     * Тип на полето в mySql таблица
     */
    public $dbFieldType = 'double';
    
    
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = 0;
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 12;
    
    
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    public $tdClass = 'rightCol';
    
    
    /**
     * Намира стойността на числото, от стринга, който е въвел потребителя
     * Входния стринг може да не е форматиран добре, също може да съдържа прости
     * аритметически изрази
     */
    public function fromVerbal($value)
    {
        $value = trim($value);
        
        $allowOct = (boolean) ($this->params['allowOct'] == 'allowOct');
        $allowHex = (boolean) ($this->params['allowHex'] == 'allowHex');
        
        
        $originalVal = $value;
        
        $from = array(',', '.', ' ', "'", '`');
        
        $to = array('.', '.', '', '', '');
        
        $value = str_replace($from, $to, trim($value));
        
        if (!strlen($value)) {
            
            return;
        }
        
        $value = $this->prepareVal($value, $allowOct, $allowHex);
        
        if (!strlen($value)) {
            
            return;
        }
        
        // Превръщаме 16-тичните числа в десетични
        //$value = trim(preg_replace('/[^0123456789]{0,1}0x([a-fA-F0-9]*)/e', "substr('\\0',0,1).hexdec('\\0')", ' '.$value));
        
        // Ако имаме букви или др. непозволени символи - връщаме грешка
        if (preg_replace('`([^+x\-*=/\(\)\d\^<>&|\.]*)`', '', $value) != $value) {
            $this->error = 'Недопустими символи в число/израз';
            
            return false;
        }
        
        if (empty($value)) {
            $value = '0';
        }
        $code = "\$val = ${value};";
        
        // Шаблон за намиране на повтарящи се знаци или изрази, които започват и/или завършват с тях
        $signP = '(\*|\/|\+|\-|\.|\,)';
        $pattern = "/(^(\s*(\*|\/)\s*))|({$signP}{1}\s*{$signP}+)|((\s*{$signP}\s*)$|([^\.|\,]*(\.|\,)[^{$signP}]*(\.|\,)[^\.|\,]*))|\=|[^0-9\(\)]{1}[^0-9\(\)]{1}/";
        
        try {
            if (!preg_match($pattern, $value) && @eval('return TRUE;' . $code)) {
                @eval($code);
                
                return (float) $val;
            }
        } catch (Throwable $e) {
            // Нищо не се прави - основно за PARSE_ERROR
        }
        
        $this->error = "Грешка при превръщане на |*<b>'" . parent::escape($originalVal) . "'</b> |в число";
        
        return false;
    }
    
    
    /**
     * Генерира input-поле за числото
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if ($this->params[0] + $this->params[1] > 0) {
            $attr['size'] = $this->params[0] + $this->params[1] + 1;
        }
        
        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    public function toVerbal($value)
    {
        if (!strlen($value)) {
            
            return;
        }
        
        $conf = core_Packs::getConfig('core');
        
        $decPoint = isset($this->params['decPoint']) ? $this->params['decPoint'] : html_entity_decode($conf->EF_NUMBER_DEC_POINT);
        $thousandsSep = Mode::is('forSearch') ? '' : isset($this->params['thousandsSep']) ?  $this->params['thousandsSep'] : html_entity_decode($conf->EF_NUMBER_THOUSANDS_SEP);
        $decimals = isset($this->params['decimals']) ? $this->params['decimals'] : EF_NUMBER_DECIMALS;
        
        // Ограничаване на максиомалния брой знаци след десетичната точка
        if(isset($this->params['maxDecimals'])) {
            $decimals = min($decimals, $this->params['maxDecimals']);
        }
       
        // Ограничаване на минималния брой знаци след десетичната точка
        if(isset($this->params['minDecimals'])) {
            $decimals = max($decimals, $this->params['minDecimals']);
        }
        
        // Ако закръгляме умно
        if ($this->params['smartRound']) {
            // Закръгляме до минимума от символи от десетичния знак или зададения брой десетични знака
            $decimals = min(strlen(substr(strrchr($value, $decPoint), 1)), $decimals);
        }
        
        // Закръгляме числото преди да го обърнем в нормален вид
        $value = round($value, $decimals);
        $value = number_format($value, $decimals, $decPoint, $thousandsSep);
        
        if (!Mode::is('text', 'plain')) {
            $value = str_replace(' ', '&nbsp;', $value);
        }
        return $value;
    }
    
    
    /**
     * Премахва символите за осмична и шестнайсетична бройна система, ако не са позволени
     *
     * @param string $double
     * @param bool   $allowOct
     * @param bool   $allowHex
     *
     * @return string
     */
    protected function prepareVal($double, $allowOct = false, $allowHex = false)
    {
        if (!$double) {
            
            return $double;
        }
        
        if ($allowOct && $allowHex) {
            
            return $double;
        }
        
        if (!$allowOct && !$allowHex) {
            $pattern = '0|0x';
        } elseif (!$allowOct) {
            $pattern = '0';
        } else {
            $pattern = '0x';
        }
        
        $double = str_replace(' ', '', $double);
        
        $pattern = "/(^|[^\.0-9]+)({$pattern})+([0-9][\.0-9]*)/";
        
        $double = preg_replace($pattern, '$1$3', $double);
        
        return $double;
    }
}
