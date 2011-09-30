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
 * Клас  'type_Double' - Тип за рационални числа
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Double extends core_Type {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'double';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $defaultValue = 0;
    
    /**
     * Минимален брой десетични цифри във вербалното представяне на стойността.
     * Ако стойността е с по-малко десетични цифри се допълва с нули.
     *
     * @var int
     */
    var $minDecimals;
    
    /**
     * Максимален брой десетични цифри във вербалното представяне на стойността.
     * Ако стойността е с повече десетични цифри, се прави закръгление (@see round())
     *
     * @var int
     */
    var $maxDecimals;
    
    function init($params = array())
    {
    	parent::init($params);
    	
    	//
    	// Определяне на (min|max)Decimals
    	//
    	
    	$this->minDecimals = $this->maxDecimals = EF_NUMBER_DECIMALS;
    	
    	if (isset($this->params['decimals'])) {
    		$decimals =  preg_split('/\s*-\s*/', $this->params['decimals'], 2);

    		if (empty($decimals[0]) || is_numeric($decimals[0])) {
    			$decimals[0] = intval($decimals[0]);
    			if (!isset($decimals[1])) {
    				$decimals[1] = $decimals[0];
    			} elseif (is_numeric($decimals[1])) {
    				$decimals[1] = intval($decimals[1]);
    			} else {
    				$decimals[1] = NULL;
    			}
    		} else {
    			$decimals[0] = $decimals[1] = EF_NUMBER_DECIMALS;
    		}
    		
    		list($this->minDecimals, $this->maxDecimals) = $decimals;
    	}
    }
    
    
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
        
        if (isset($this->maxDecimals)) {
        	// Ограничаваме броя на десетичните цифри до зададения максимум
        	$value = round($value, $this->maxDecimals, PHP_ROUND_HALF_UP);
        }
        
        // Разцепваме стойността на цяла и дробна част
        list($num, $frac) = explode('.', (string)$value);
        
        if (strlen($frac) < $this->minDecimals) {
        	// Допълваме десетичните цифри с нули за да стане броят им поне колкото зададения 
        	// минимум
        	$frac = str_pad($frac, $this->minDecimals - strlen($frac), '0', STR_PAD_RIGHT);
        }
        
        $decPoint = EF_NUMBER_DEC_POINT;
        $thousandsSep = EF_NUMBER_THOUSANDS_SEP;
        
        // Форматираме цялата част
        $value = number_format($num, 0, $decPoint, $thousandsSep);
        
        // Сглобяваме обратно цялата и дробната част
        if ($frac != '') {
        	 $value .= $decPoint . $frac;
        }
        
        return str_replace(' ', '&nbsp;', $value);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getCellAttr()
    {
        return 'align="right" nowrap';
    }
}