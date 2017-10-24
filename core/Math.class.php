<?php



/**
 * Клас 'core_Math' ['math'] - Математически функции
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Math
{

	/**
     * "Интелигентно" закръгляне, което гарантира минимален брой
     * (зададен чрез конфигурация) знаци след закръглянето
     *
     * @param   double  $number             число, което подлежи на закръгляне
     * @param   int     $fractionalLen      текуща максимална дробна част
     * @param   int     $significantDigits  минимален брой значещи цифри след закръглянето
     *
     * @return  double                      закръгленo число
     */
	public static function roundNumber($number, &$fractionalLen = 0, $significantDigits = NULL)
	{
        if(!$fractionalLen){
        	$fractionalLen = 0;
        }
		
		if($significantDigits === NULL) {
            
            // Вземаме конфигурацията на пакета ef	    
            $conf = core_Packs::getConfig('core');

            $significantDigits = $conf->EF_ROUND_SIGNIFICANT_DIGITS;
        }
        
        $r = 0;
        if (round($number, 10)) {
            $r = log10(abs($number));
        }
        
	    // Плаваща, лимитирана от долу прецизност
	    $precision =  max($fractionalLen, round($significantDigits - $r));
		
	    // Закръгляваме
	    $number = round($number, $precision);
	    
	    // Дължината на дробната част
        $thisFractionalLen = strlen(substr(strrchr($number, "."), 1));
       
        if($thisFractionalLen > $fractionalLen) {
            
            // Записваме новата по-голяма част
            $fractionalLen = $thisFractionalLen;

        } elseif($thisFractionalLen < $fractionalLen) {

            // Падваме с 0-ли отдясно
            if(floor($number) != $number){
            	$number = str_pad($number, $fractionalLen, "0", STR_PAD_RIGHT);
            }
		}
	    
	    return $number;
	}
	
	
	/**
	 * Функция намираща остатъка на делението на две реални числа
	 * 
	 * @param double $x
	 * @param double $y
	 * @return double $r
	 */
	public static function fmod($x, $y)
	{
		$x = (double)$x;
		$y = (double)$y;
		expect($y != 0, 'Опит за деление на нула');
		
		$r = $x - floor($x / $y) * $y;
		
		return $r;
	}
}