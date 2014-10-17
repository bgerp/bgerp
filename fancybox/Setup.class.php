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
	 * Пътища до CSS файлове
	 */
	var $commonCSS = "[#FANCYBOX_PATH#]/jquery.fancybox.css";
	
	
	/**
	 * Пътища до JS файлове
	 */
	var $commonJS = "[#FANCYBOX_PATH#]/jquery.fancybox.js";
	
}

