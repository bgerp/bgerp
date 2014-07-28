<?php


/**
 * Път до външния пакет
 */
defIfNot('FANCYBOX_PATH', 'fancybox/1.3.4');


/**
 * Клас 'fancybox_Fancybox'
 *
 * Съдържа необходимите функции за използването на
 * Fancybox
 *
 *
 * @category  vendors
 * @package   fancybox
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 * @link      http://fancybox.net/
 */
class fancybox_Setup extends core_ProtoSetup
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
		$conf = core_Packs::getConfig('fancybox');
	
		// Пътя до js файла
		$jsFile = $conf->FANCYBOX_PATH . '/jquery.fancybox.js';
		$cssAnaJsArr['js'][$jsFile] = $jsFile;
		 
		// Пътя до css файла
		$cssFile = $conf->FANCYBOX_PATH . '/jquery.fancybox.css';
		$cssAnaJsArr['css'][$cssFile] = $cssFile;
	
		return $cssAnaJsArr;
	}
}

