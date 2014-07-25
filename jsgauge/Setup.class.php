<?php


/**
 * Път до външния файл
 */
defIfNot('GAUGE_PATH', 'jsgauge/0.4.1');

/**
 * Клас 'jsgauge_Gauge'
 *
 * Клас, който служи за създаване на Gauge.
 * Съдържа необходимите функции за използването на
 * Gauge
 *
 *
 * @category  vendors
 * @package   jsgauge
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      http://code.google.com/p/jsgauge/
 */
class jsgauge_Setup extends core_ProtoSetup
{
	/**
	 * Връща масив с css и js файловете дефинирани в commonJS и commonCSS
	 *
	 * @return array - Двумерен масив с 'css' и 'js' пътищатата
	 *
	 * @see core_ProtoSetup->getCommonCssAndJs()
	 */
	function getCommonCssAndJs()
	{
		$cssAnaJsArr = parent::getCommonCssAndJs();
		$conf = core_Packs::getConfig('jsgauge');
	
		// Пътя до js файла
		$jsFile = $conf->GAUGE_PATH . '/' . "gauge.js";
		$cssAnaJsArr['js'][$jsFile] = $jsFile;
		
		return $cssAnaJsArr;
	}
}

