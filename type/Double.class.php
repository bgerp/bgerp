<?php

/**
* Колко цифри след запетаята да показваме по подразбиране?
*/
defIfNot('EF_NUMBER_DECIMALS', 4);


/**
* Кой символ за десетична точка да използваме?
*/
defIfNot('EF_NUMBER_DEC_POINT', ',');


/**
* Кой символ да използваме за разделител на хилядите?
*/
defIfNot('EF_NUMBER_THOUSANDS_SEP', ' ');


/**
* Клас 'type_Double' - Тип за рационални числа
*
*
* @category Experta Framework
* @package type
* @author Milen Georgiev <milen@download.bg>
* @copyright 2006-2010 Experta OOD
* @license GPL 2
* @version CVS: $Id:$
* @link
* @since v 0.1
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
    * Намира стойността на числото, от стринга, който е въвел потребителя
    * Входния стринг може да не е форматиран фдобре, също може да съдържа прости
    * аритметически изрази
    */
    function fromVerbal($val)
    {
        $originalVal = $val;
        
        $from = array(',', EF_TYPE_DOUBLE_DEC_POINT, ' ', "'", EF_TYPE_DOUBLE_THOUSANDS_SEP);
        
        $to = array('.', '.', '','', '');
        
        $val = str_replace($from, $to, trim($val));
        
        if( $val === '') return NULL;
        
        // Превръщаме 16-тичните числа в десетични
        //$val = trim(preg_replace('/[^0123456789]{0,1}0x([a-fA-F0-9]*)/e', "substr('\\0',0,1).hexdec('\\0')", ' '.$val));
        
        // Ако имаме букви или др. непозволени символи - връщаме грешка
        if(preg_replace('`([^+x\-*=/\(\)\d\^<>&|\.]*)`', '', $val) != $val) {
            $this->error = "Недопустими символи в число/израз";
            
            return FALSE;
        }
        
        if(empty($val)) $val = '0';
        $code = "\$val = $val;";
        
        if( @eval('return TRUE;' . $code) ) {
            eval($code);
            
            return (float) $val;
        } else {
            $this->error = "Грешка при превръщане на |*<b>'{$originalVal}'</b> |в число";
            
            return FALSE;
        }
    }
    
    
   /**
    * Генерира input-поле за числото
    */
    function renderInput_($name, $value="", $attr = array())
    {
        if (strpos($attr['style'], 'text-align:') === FALSE) {
            $attr['style'] .= 'text-align:right;';
        }
        
        if($this->params[0] + $this->params[1] > 0 ) {
            $attr['size'] = $this->params[0] + $this->params[1]+1;
        }
        
        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
    
    
   /**
    * Форматира числото в удобна за четене форма
    */
    function toVerbal($value)
    {
        if(!isset($value)) return NULL;
        
        setIfNot($decimals, $this->params['decimals'], EF_NUMBER_DECIMALS);
        
        $decPoint     = EF_NUMBER_DEC_POINT;
        $thousandsSep = EF_NUMBER_THOUSANDS_SEP;
        
        $value = number_format($value, $decimals, $decPoint, $thousandsSep);
        
        return str_replace(' ', '&nbsp;', $value);
    }
    
    
   /**
    * @todo Чака за документация...
    */
    function getCellAttr()
    {
        return 'align="right" nowrap';
    }
}